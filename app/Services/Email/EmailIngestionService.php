<?php

namespace App\Services\Email;

use App\Contracts\Email\EmailProviderInterface;
use App\Enums\EmailDirection;
use App\Enums\EmailProvider;
use App\Jobs\AnalyzeInboundEmailJob;
use App\Models\EmailAccount;
use App\Models\EmailMessage;
use App\Models\SupplierContact;
use App\Models\SupplierOrder;
use App\Services\Email\Providers\GmailEmailProvider;
use App\Services\Email\Providers\ImapEmailProvider;
use App\Services\Email\Providers\ManualEmailProvider;
use App\Services\Email\Providers\MicrosoftGraphEmailProvider;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EmailIngestionService
{
    /**
     * @param  array<string, mixed>  $options
     * @return array{stored_count:int,duplicate_count:int,stored:list<EmailMessage>,duplicates:list<string>}
     */
    public function ingest(EmailAccount $account, array $options = []): array
    {
        $provider = $options['provider'] ?? $this->providerFor($account);
        $messages = $provider->fetchNewMessages($account, $options);
        $stored = [];
        $duplicates = [];

        foreach ($messages as $message) {
            $messageId = isset($message['message_id']) ? (string) $message['message_id'] : null;

            if ($messageId !== null && $this->isDuplicate($account, $messageId)) {
                $duplicates[] = $messageId;

                continue;
            }

            $emailMessage = $this->storeMessage($account, $message);
            $stored[] = $emailMessage;

            if ((bool) ($options['dispatch_analysis'] ?? false)) {
                AnalyzeInboundEmailJob::dispatch($emailMessage->id);
            }
        }

        return [
            'stored_count' => count($stored),
            'duplicate_count' => count($duplicates),
            'stored' => $stored,
            'duplicates' => $duplicates,
        ];
    }

    private function providerFor(EmailAccount $account): EmailProviderInterface
    {
        return match ($account->provider) {
            EmailProvider::Gmail => app(GmailEmailProvider::class),
            EmailProvider::MicrosoftGraph => app(MicrosoftGraphEmailProvider::class),
            EmailProvider::ImapSmtp => app(ImapEmailProvider::class),
            EmailProvider::Manual => app(ManualEmailProvider::class),
        };
    }

    private function isDuplicate(EmailAccount $account, string $messageId): bool
    {
        return EmailMessage::query()
            ->where('company_id', $account->company_id)
            ->where('message_id', $messageId)
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function storeMessage(EmailAccount $account, array $message): EmailMessage
    {
        $fromEmail = isset($message['from_email']) ? (string) $message['from_email'] : null;
        $subject = isset($message['subject']) ? (string) $message['subject'] : null;
        $threadContext = $this->threadContext($account, $message['thread_id'] ?? null);
        $relatedSupplierId = $this->guessSupplierId($account, $fromEmail) ?? $threadContext['related_supplier_id'];
        $relatedSupplierOrderId = $this->guessSupplierOrderId($account, $subject) ?? $threadContext['related_supplier_order_id'];

        $emailMessage = EmailMessage::query()->create([
            'company_id' => $account->company_id,
            'email_account_id' => $account->id,
            'direction' => EmailDirection::Inbound,
            'message_id' => $message['message_id'] ?? null,
            'thread_id' => $message['thread_id'] ?? null,
            'from_email' => $fromEmail,
            'to_json' => $this->normalizeList($message['to'] ?? []),
            'cc_json' => $this->normalizeList($message['cc'] ?? []),
            'subject' => $subject,
            'body_text' => $message['body_text'] ?? null,
            'body_html' => $message['body_html'] ?? null,
            'received_at' => $message['received_at'] ?? now(),
            'sent_at' => null,
            'related_supplier_id' => $relatedSupplierId,
            'related_supplier_order_id' => $relatedSupplierOrderId,
            'status' => 'received',
            'raw_headers_json' => is_array($message['raw_headers'] ?? null) ? $message['raw_headers'] : [],
        ]);

        foreach ($this->normalizeAttachments($message['attachments'] ?? []) as $attachment) {
            $emailMessage->attachments()->create($attachment);
        }

        return $emailMessage->load('attachments');
    }

    private function guessSupplierId(EmailAccount $account, ?string $fromEmail): ?int
    {
        if ($fromEmail === null) {
            return null;
        }

        $contact = SupplierContact::query()
            ->select(['id', 'supplier_id', 'email'])
            ->where('email', $fromEmail)
            ->whereHas('supplier', fn ($query) => $query->where('company_id', $account->company_id))
            ->first();

        return $contact?->supplier_id;
    }

    /**
     * @return array{related_supplier_id:?int,related_supplier_order_id:?int}
     */
    private function threadContext(EmailAccount $account, mixed $threadId): array
    {
        if (! is_string($threadId) || $threadId === '') {
            return [
                'related_supplier_id' => null,
                'related_supplier_order_id' => null,
            ];
        }

        $emailMessage = EmailMessage::query()
            ->select(['id', 'company_id', 'thread_id', 'related_supplier_id', 'related_supplier_order_id'])
            ->where('company_id', $account->company_id)
            ->where('thread_id', $threadId)
            ->where(function ($query): void {
                $query
                    ->whereNotNull('related_supplier_id')
                    ->orWhereNotNull('related_supplier_order_id');
            })
            ->latest('id')
            ->first();

        return [
            'related_supplier_id' => $emailMessage?->related_supplier_id,
            'related_supplier_order_id' => $emailMessage?->related_supplier_order_id,
        ];
    }

    private function guessSupplierOrderId(EmailAccount $account, ?string $subject): ?int
    {
        if ($subject === null || $subject === '') {
            return null;
        }

        $subject = Str::lower($subject);

        return SupplierOrder::query()
            ->select(['id', 'company_id', 'order_number'])
            ->where('company_id', $account->company_id)
            ->latest('id')
            ->limit(200)
            ->get()
            ->first(fn (SupplierOrder $order): bool => Str::contains($subject, Str::lower($order->order_number)))
            ?->id;
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
     * @return list<array<string, mixed>>
     */
    private function normalizeAttachments(mixed $attachments): array
    {
        if (! is_array($attachments)) {
            return [];
        }

        return collect($attachments)
            ->filter(fn (mixed $attachment): bool => is_array($attachment))
            ->map(function (array $attachment): array {
                $filename = (string) ($attachment['original_filename'] ?? $attachment['filename'] ?? 'attachment.bin');
                $storedPath = (string) ($attachment['stored_path'] ?? '');
                $content = $attachment['content'] ?? null;

                if ($storedPath === '' && is_string($content)) {
                    $storedPath = sprintf('email-attachments/%s/%s', now()->format('Ymd'), Str::uuid()->toString().'-'.$filename);
                    Storage::put($storedPath, $content);
                }

                return [
                    'original_filename' => $filename,
                    'stored_path' => $storedPath,
                    'mime_type' => $attachment['mime_type'] ?? null,
                    'size_bytes' => isset($attachment['size_bytes'])
                        ? (int) $attachment['size_bytes']
                        : (is_string($content) ? strlen($content) : null),
                    'checksum' => $attachment['checksum'] ?? (is_string($content) ? hash('sha256', $content) : null),
                ];
            })
            ->values()
            ->all();
    }
}
