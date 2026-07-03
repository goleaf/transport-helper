<?php

namespace App\Services\Supply\SupplierOrders;

use App\Enums\EmailDirection;
use App\Enums\ExportFileStatus;
use App\Enums\SupplierOrderStatus;
use App\Models\EmailAttachment;
use App\Models\EmailMessage;
use App\Models\ExportFile;
use App\Models\SupplierOrder;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SupplierOrderEmailDraftService
{
    public function __construct(
        private readonly SupplierOrderExportService $exportService,
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function prepareDraft(SupplierOrder $order, array $options = [], ?User $user = null): array
    {
        return DB::transaction(function () use ($order, $options, $user): array {
            $order->loadMissing([
                'company:id,name',
                'supplier:id,name,default_language',
                'supplier.contacts:id,supplier_id,name,email,receives_orders,is_active',
                'items:id,supplier_order_id,product_id,ordered_quantity',
                'items.product:id,sku,name',
            ]);

            if ($order->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'supplier_order' => 'Supplier order email draft requires at least one item.',
                ]);
            }

            $recipients = $this->orderRecipients($order);

            if ($recipients === []) {
                throw ValidationException::withMessages([
                    'supplier_contacts' => 'Supplier order email draft requires at least one active order contact.',
                ]);
            }

            [$exportFile, $warnings] = $this->resolveAttachmentExport($order, $options, $user);
            $language = $this->resolveLanguage($order, $options);
            $subject = (string) ($options['subject'] ?? sprintf('Purchase order %s', $order->order_number));
            $bodyText = (string) ($options['body_text'] ?? $this->defaultBody($order, $language, $user));
            $cc = $this->normalizeEmailList($options['cc'] ?? []);
            $emailAccountId = $options['email_account_id'] ?? null;

            $email = $this->draftEmailFor($order) ?? new EmailMessage;
            $email->fill([
                'company_id' => $order->company_id,
                'email_account_id' => $emailAccountId,
                'direction' => EmailDirection::Outbound,
                'from_email' => $this->fromEmail(),
                'to_json' => $recipients,
                'cc_json' => $cc,
                'subject' => $subject,
                'body_text' => $bodyText,
                'body_html' => null,
                'related_supplier_id' => $order->supplier_id,
                'related_supplier_order_id' => $order->id,
                'status' => 'draft',
                'raw_headers_json' => [
                    'language' => $language,
                    'template' => 'supplier_order_email_v1',
                ],
            ]);
            $email->save();

            $attachments = [];

            if ($exportFile instanceof ExportFile) {
                $attachments[] = $this->attachExportFile($email, $exportFile);
            }

            $oldStatus = $this->statusValue($order->status);
            $order->forceFill([
                'status' => SupplierOrderStatus::EmailPrepared,
                'email_message_id' => (string) $email->id,
                'email_subject' => $subject,
                'email_body' => $bodyText,
            ])->save();

            $this->auditLogService->logDecision('supplier_email_draft_prepared', $order, $user, [
                'supplier_order_id' => $order->id,
                'email_message_id' => $email->id,
                'recipients' => $recipients,
                'cc' => $cc,
                'subject' => $subject,
                'attachment_count' => count($attachments),
                'export_file_id' => $exportFile?->id,
                'auto_export' => ($options['auto_export'] ?? true) !== false,
                'warnings' => $warnings,
            ]);
            $this->auditLogService->logStatusChanged($order, $oldStatus, SupplierOrderStatus::EmailPrepared->value, $user, [
                'email_message_id' => $email->id,
            ]);

            return [
                'email_message' => $email->load('attachments'),
                'supplier_order' => $order->fresh(),
                'attachments' => $attachments,
                'warnings' => $warnings,
            ];
        });
    }

    /**
     * @return array{0:ExportFile|null,1:list<string>}
     */
    protected function resolveAttachmentExport(SupplierOrder $order, array $options, ?User $user): array
    {
        $warnings = [];
        $exportFile = null;

        if (! empty($options['export_file_id'])) {
            $exportFile = ExportFile::query()
                ->whereKey($options['export_file_id'])
                ->where('related_model_type', $order::class)
                ->where('related_model_id', $order->id)
                ->first();

            if (! $exportFile instanceof ExportFile) {
                throw ValidationException::withMessages([
                    'export_file_id' => 'Selected export file does not belong to this supplier order.',
                ]);
            }

            return [$exportFile, $warnings];
        }

        $exportFile = ExportFile::query()
            ->where('related_model_type', $order::class)
            ->where('related_model_id', $order->id)
            ->whereIn('status', [ExportFileStatus::Stored->value, 'ready'])
            ->latest('id')
            ->first();

        if (! $exportFile instanceof ExportFile && ($options['auto_export'] ?? true) !== false) {
            $result = $this->exportService->export(
                $order,
                (string) ($options['auto_export_format'] ?? config('supply.exports.default_supplier_order_format', 'excel_csv')),
                [],
                $user,
            );
            $exportFile = $result['export_file'];
        }

        if (! $exportFile instanceof ExportFile) {
            $warnings[] = 'missing_attachment';
        }

        return [$exportFile, $warnings];
    }

    /**
     * @return list<string>
     */
    protected function orderRecipients(SupplierOrder $order): array
    {
        return $order->supplier?->contacts
            ->filter(fn ($contact): bool => $contact->is_active && $contact->receives_orders && filled($contact->email))
            ->pluck('email')
            ->values()
            ->all() ?? [];
    }

    protected function draftEmailFor(SupplierOrder $order): ?EmailMessage
    {
        if (is_string($order->email_message_id) && ctype_digit($order->email_message_id)) {
            $email = EmailMessage::query()
                ->whereKey((int) $order->email_message_id)
                ->where('status', 'draft')
                ->first();

            if ($email instanceof EmailMessage) {
                return $email;
            }
        }

        return $order->emailMessages()
            ->where('direction', EmailDirection::Outbound->value)
            ->where('status', 'draft')
            ->latest('id')
            ->first();
    }

    protected function attachExportFile(EmailMessage $email, ExportFile $exportFile): EmailAttachment
    {
        return $email->attachments()->firstOrCreate(
            ['stored_path' => $exportFile->stored_path],
            [
                'original_filename' => $exportFile->filename,
                'mime_type' => $exportFile->mime_type,
                'size_bytes' => Storage::exists($exportFile->stored_path) ? Storage::size($exportFile->stored_path) : null,
                'checksum' => Storage::exists($exportFile->stored_path) ? hash('sha256', Storage::get($exportFile->stored_path)) : null,
            ]
        );
    }

    protected function resolveLanguage(SupplierOrder $order, array $options): string
    {
        $language = strtolower((string) ($options['language'] ?? $order->supplier?->default_language ?? 'en'));

        return $language === 'lt' ? 'lt' : 'en';
    }

    protected function defaultBody(SupplierOrder $order, string $language, ?User $user): string
    {
        $signoff = $user?->name ?? $order->company?->name ?? 'Supply team';

        if ($language === 'lt') {
            return implode(PHP_EOL, [
                'Sveiki,',
                '',
                sprintf('Prisegame mūsų užsakymą %s.', $order->order_number),
                '',
                'Prašome patvirtinti:',
                '- ar užsakymas gautas;',
                '- patvirtintus kiekius;',
                '- numatomą paruošimo datą;',
                '- numatomą išsiuntimo datą;',
                '- ar yra neprieinamų prekių arba pakeitimų.',
                '',
                'Pagarbiai,',
                $signoff,
            ]);
        }

        return implode(PHP_EOL, [
            'Hello,',
            '',
            sprintf('Please find attached our purchase order %s.', $order->order_number),
            '',
            'Please confirm:',
            '- received order;',
            '- confirmed quantities;',
            '- expected ready date;',
            '- expected shipping date;',
            '- any unavailable items or changes.',
            '',
            'Best regards,',
            $signoff,
        ]);
    }

    protected function fromEmail(): ?string
    {
        return config('supply.email.default_from') ?: config('mail.from.address');
    }

    /**
     * @return list<string>
     */
    protected function normalizeEmailList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return collect($value)
            ->filter(fn ($email): bool => is_string($email) && trim($email) !== '')
            ->map(fn (string $email): string => trim($email))
            ->values()
            ->all();
    }

    protected function statusValue(mixed $status): ?string
    {
        return $status instanceof \BackedEnum ? (string) $status->value : ($status === null ? null : (string) $status);
    }
}
