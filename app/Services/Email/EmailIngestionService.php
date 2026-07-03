<?php

namespace App\Services\Email;

use App\Contracts\Email\EmailProviderInterface;
use App\Enums\EmailDirection;
use App\Exceptions\NotConfiguredYetException;
use App\Jobs\AnalyzeInboundEmailJob;
use App\Models\Company;
use App\Models\EmailAccount;
use App\Models\EmailMessage;
use App\Models\User;
use App\Services\AI\Email\AiEmailAnalysisService;
use App\Services\Audit\AuditLogService;
use App\Services\Email\Providers\GmailEmailProviderPlaceholder;
use App\Services\Email\Providers\ImapEmailProviderPlaceholder;
use App\Services\Email\Providers\ManualEmailProvider;
use App\Services\Email\Providers\MicrosoftGraphEmailProviderPlaceholder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Throwable;

class EmailIngestionService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly SupplierEmailMatcher $supplierMatcher,
        private readonly SupplierOrderEmailMatcher $orderMatcher,
        private readonly EmailAttachmentStorageService $attachmentStorageService,
        private readonly AiEmailAnalysisService $analysisService,
    ) {}

    /**
     * @param  EmailAccount|array<string, mixed>|null  $accountOrOptions
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function ingest(
        Company|EmailAccount $companyOrAccount,
        EmailAccount|array|null $accountOrOptions = null,
        ?string $providerName = null,
        array $options = [],
        ?User $user = null,
    ): array {
        [$company, $account, $providerName, $options] = $this->normalizeArguments($companyOrAccount, $accountOrOptions, $providerName, $options);

        $this->auditLogService->write('email_ingestion_started', $company, $user, null, null, [
            'provider' => $providerName,
            'email_account_id' => $account?->getKey(),
        ], $company->getKey());

        try {
            $messages = $this->resolveProvider($providerName)->fetchMessages($account, $options);
            $stored = [];
            $duplicates = [];
            $warnings = [];

            foreach ($messages as $message) {
                $message = $this->normalizeMessage($message);
                $duplicate = $this->findDuplicate($company, $account, $message);

                if ($duplicate instanceof EmailMessage) {
                    $duplicates[] = (string) ($message['message_id'] ?? $message['_dedupe_hash']);
                    $this->auditLogService->write('email_duplicate_skipped', $duplicate, $user, null, null, [
                        'message_id' => $message['message_id'] ?? null,
                        'dedupe_hash' => $message['_dedupe_hash'] ?? null,
                    ], $company->getKey());

                    continue;
                }

                $supplierMatch = $this->supplierMatcher->match($company, $message['from_email'] ?? null);
                $orderMatch = $this->orderMatcher->match($company, $message, $supplierMatch['supplier_id']);
                $rowWarnings = array_values(array_unique(array_merge($supplierMatch['warnings'], $orderMatch['warnings'])));
                $status = $this->statusForMatch($supplierMatch, $orderMatch, $rowWarnings);

                $email = EmailMessage::query()->create([
                    'company_id' => $company->getKey(),
                    'email_account_id' => $account?->getKey(),
                    'direction' => EmailDirection::Inbound,
                    'message_id' => $message['message_id'] ?? null,
                    'thread_id' => $message['thread_id'] ?? null,
                    'from_email' => $message['from_email'] ?? null,
                    'to_json' => $this->normalizeList($message['to'] ?? []),
                    'cc_json' => $this->normalizeList($message['cc'] ?? []),
                    'subject' => $message['subject'] ?? null,
                    'body_text' => $message['body_text'] ?? null,
                    'body_html' => $message['body_html'] ?? null,
                    'received_at' => $message['received_at'],
                    'sent_at' => null,
                    'related_supplier_id' => $supplierMatch['supplier_id'],
                    'related_supplier_order_id' => $orderMatch['supplier_order_id'],
                    'status' => $status,
                    'raw_headers_json' => is_array($message['raw_headers'] ?? null) ? $message['raw_headers'] : [],
                ]);

                $attachments = $this->attachmentStorageService->storeAttachments($email, is_array($message['attachments'] ?? null) ? $message['attachments'] : []);
                $email->setRelation('attachments', collect($attachments));
                $stored[] = $email;
                $warnings = array_merge($warnings, $rowWarnings);

                $this->auditLogService->write('email_received', $email, $user, null, null, [
                    'email_message_id' => $email->getKey(),
                    'message_id' => $email->message_id,
                    'from_email' => $email->from_email,
                    'subject' => $email->subject,
                    'related_supplier_id' => $email->related_supplier_id,
                    'related_supplier_order_id' => $email->related_supplier_order_id,
                    'supplier_match_method' => $supplierMatch['method'],
                    'order_match_method' => $orderMatch['method'],
                    'attachment_count' => count($attachments),
                    'warnings' => $rowWarnings,
                ], $company->getKey());

                if ((bool) ($options['analyze'] ?? false)) {
                    if ((bool) ($options['sync_analysis'] ?? false)) {
                        $this->analysisService->analyze($email, [
                            'analyzer' => $options['analyzer'] ?? null,
                            'fake_output' => $options['fake_output'] ?? null,
                        ], $user);
                    } else {
                        AnalyzeInboundEmailJob::dispatch($email->getKey(), [
                            'analyzer' => $options['analyzer'] ?? null,
                        ]);
                        $email->forceFill(['status' => 'analysis_pending'])->save();
                    }
                }
            }

            $summary = [
                'fetched_count' => count($messages),
                'stored_count' => count($stored),
                'duplicate_count' => count($duplicates),
                'failed_count' => 0,
                'linked_supplier_count' => collect($stored)->filter(fn (EmailMessage $email): bool => $email->related_supplier_id !== null)->count(),
                'linked_order_count' => collect($stored)->filter(fn (EmailMessage $email): bool => $email->related_supplier_order_id !== null)->count(),
                'stored' => $stored,
                'messages' => $stored,
                'duplicates' => $duplicates,
                'warnings' => array_values(array_unique($warnings)),
            ];
            $summary['summary'] = collect($summary)
                ->except(['stored', 'messages'])
                ->all();

            $this->auditLogService->write('email_ingestion_completed', $company, $user, null, null, [
                'provider' => $providerName,
                'total_messages' => count($messages),
                'stored_count' => $summary['stored_count'],
                'duplicate_count' => $summary['duplicate_count'],
                'warnings' => $summary['warnings'],
            ], $company->getKey());

            return $summary;
        } catch (Throwable $exception) {
            $this->auditLogService->write('email_ingestion_failed', $company, $user, null, null, [
                'provider' => $providerName,
                'error' => $exception->getMessage(),
            ], $company->getKey());

            throw $exception;
        }
    }

    private function resolveProvider(string $providerName): EmailProviderInterface
    {
        return match ($providerName) {
            'manual' => app(ManualEmailProvider::class),
            'gmail' => app(GmailEmailProviderPlaceholder::class),
            'microsoft_graph' => app(MicrosoftGraphEmailProviderPlaceholder::class),
            'imap', 'imap_smtp' => app(ImapEmailProviderPlaceholder::class),
            default => throw NotConfiguredYetException::forAdapter('email_provider_'.$providerName),
        };
    }

    /**
     * @param  EmailAccount|array<string, mixed>|null  $accountOrOptions
     * @param  array<string, mixed>  $options
     * @return array{0:Company,1:?EmailAccount,2:string,3:array<string,mixed>}
     */
    private function normalizeArguments(Company|EmailAccount $companyOrAccount, EmailAccount|array|null $accountOrOptions, ?string $providerName, array $options): array
    {
        if ($companyOrAccount instanceof EmailAccount) {
            $account = $companyOrAccount;
            $company = $account->company()->select(['id', 'name'])->firstOrFail();
            $options = is_array($accountOrOptions) ? $accountOrOptions : $options;
            $providerName = $providerName ?? (string) ($options['provider_name'] ?? $options['provider'] ?? $account->provider->value);

            return [$company, $account, $providerName, $options];
        }

        $company = $companyOrAccount;
        $account = $accountOrOptions instanceof EmailAccount ? $accountOrOptions : null;
        $providerName = $providerName ?? (string) ($options['provider_name'] ?? $options['provider'] ?? config('supply.email_ingestion.default_provider', 'manual'));

        return [$company, $account, $providerName, $options];
    }

    /**
     * @param  array<string, mixed>  $message
     * @return array<string, mixed>
     */
    private function normalizeMessage(array $message): array
    {
        $receivedAt = $message['received_at'] ?? now()->toDateTimeString();
        $message['received_at'] = Carbon::parse((string) $receivedAt)->toDateTimeString();
        $message['from_email'] = isset($message['from_email']) ? Str::lower(trim((string) $message['from_email'])) : null;
        $message['subject'] = isset($message['subject']) ? (string) $message['subject'] : null;
        $message['body_text'] = isset($message['body_text']) ? (string) $message['body_text'] : null;
        $message['_dedupe_hash'] = hash('sha256', implode('|', [
            (string) ($message['from_email'] ?? ''),
            (string) ($message['subject'] ?? ''),
            (string) ($message['body_text'] ?? ''),
            (string) $message['received_at'],
        ]));

        return $message;
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function findDuplicate(Company $company, ?EmailAccount $account, array $message): ?EmailMessage
    {
        $messageId = $message['message_id'] ?? null;

        if (is_string($messageId) && $messageId !== '') {
            $query = EmailMessage::query()
                ->select(['id', 'company_id', 'email_account_id', 'message_id'])
                ->where('message_id', $messageId);

            if ($account instanceof EmailAccount) {
                $query->where('email_account_id', $account->getKey());
            } else {
                $query->where('company_id', $company->getKey());
            }

            return $query->first();
        }

        return EmailMessage::query()
            ->select(['id', 'company_id', 'from_email', 'subject', 'body_text', 'received_at'])
            ->where('company_id', $company->getKey())
            ->where('from_email', $message['from_email'])
            ->where('subject', $message['subject'])
            ->where('body_text', $message['body_text'])
            ->where('received_at', $message['received_at'])
            ->first();
    }

    /**
     * @return list<string>
     */
    private function normalizeList(mixed $value): array
    {
        if (is_string($value)) {
            return [$value];
        }

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, fn (mixed $item): bool => is_string($item) && $item !== ''));
    }

    /**
     * @param  array<string, mixed>  $supplierMatch
     * @param  array<string, mixed>  $orderMatch
     * @param  list<string>  $warnings
     */
    private function statusForMatch(array $supplierMatch, array $orderMatch, array $warnings): string
    {
        if (in_array('supplier_domain_ambiguous', $warnings, true) || in_array('multiple_order_matches', $warnings, true)) {
            return 'needs_review';
        }

        if ($supplierMatch['supplier_id'] !== null || $orderMatch['supplier_order_id'] !== null) {
            return 'linked';
        }

        return $warnings === [] ? 'stored' : 'needs_review';
    }
}
