<?php

namespace App\Services\Supply;

use App\Contracts\Supply\SupplierOrderTemplateRendererInterface;
use App\Enums\EmailDirection;
use App\Enums\SupplierOrderStatus;
use App\Models\AuditLog;
use App\Models\EmailMessage;
use App\Models\ExportFile;
use App\Models\SupplierOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SupplierOrderEmailDraftService implements SupplierOrderTemplateRendererInterface
{
    /**
     * @param  array<string, mixed>  $context
     * @return array{subject:string,body_text:string,body_html:?string,to:list<string>,cc:list<string>,attachments:list<array<string,mixed>>,language:?string}
     */
    public function render(SupplierOrder $order, array $context = []): array
    {
        $order->loadMissing([
            'company:id,name,default_currency',
            'supplier.contacts' => function ($query): void {
                $query
                    ->select(['id', 'supplier_id', 'name', 'email', 'receives_orders', 'is_active'])
                    ->where('is_active', true)
                    ->where('receives_orders', true)
                    ->orderBy('id');
            },
            'items.product:id,sku,name',
        ]);

        $contacts = $order->supplier?->contacts ?? collect();
        $to = $contacts
            ->pluck('email')
            ->filter()
            ->values()
            ->all();

        if ($to === []) {
            throw ValidationException::withMessages([
                'supplier_contacts' => 'Supplier order email draft requires at least one active order contact.',
            ]);
        }

        $language = (string) ($context['language'] ?? $order->supplier?->default_language ?? 'en');
        $subject = sprintf('Purchase order %s from %s', $order->order_number, $order->company?->name);
        $lines = [
            sprintf('Dear %s team,', $order->supplier?->name),
            '',
            sprintf('Please find purchase order %s below.', $order->order_number),
            '',
            'Order summary:',
        ];

        foreach ($order->items as $item) {
            $lines[] = sprintf(
                '- %s %s: %s',
                $item->product?->sku,
                $item->product?->name,
                $item->ordered_quantity,
            );
        }

        $lines[] = '';
        $lines[] = 'Please confirm quantities, ready date, shipping date, and expected arrival date.';
        $lines[] = '';
        $lines[] = 'Best regards,';
        $lines[] = (string) ($order->company?->name ?? 'Supply team');

        return [
            'subject' => $subject,
            'body_text' => implode(PHP_EOL, $lines),
            'body_html' => null,
            'to' => $to,
            'cc' => [],
            'attachments' => $this->attachmentPayloads($order),
            'language' => $language,
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function prepareDraft(SupplierOrder $order, User $user, array $context = []): EmailMessage
    {
        return DB::transaction(function () use ($order, $user, $context): EmailMessage {
            $draft = $this->render($order, $context);

            $emailMessage = EmailMessage::query()->create([
                'company_id' => $order->company_id,
                'direction' => EmailDirection::Outbound,
                'message_id' => null,
                'thread_id' => sprintf('supplier-order-%s', $order->id),
                'from_email' => config('mail.from.address') ?: 'supply@example.test',
                'to_json' => $draft['to'],
                'cc_json' => $draft['cc'],
                'subject' => $draft['subject'],
                'body_text' => $draft['body_text'],
                'body_html' => $draft['body_html'],
                'sent_at' => null,
                'related_supplier_id' => $order->supplier_id,
                'related_supplier_order_id' => $order->id,
                'status' => 'draft',
                'raw_headers_json' => [
                    'language' => $draft['language'],
                    'template' => 'supplier_order_request_v1',
                ],
            ]);

            foreach ($draft['attachments'] as $attachment) {
                $emailMessage->attachments()->create([
                    'original_filename' => $attachment['filename'],
                    'stored_path' => $attachment['stored_path'],
                    'mime_type' => $attachment['mime_type'],
                    'size_bytes' => $attachment['size_bytes'],
                    'checksum' => $attachment['checksum'],
                ]);
            }

            $oldValues = $order->only(['status']);

            $order->forceFill([
                'status' => SupplierOrderStatus::EmailPrepared,
            ])->save();

            $this->writeAuditLog(
                eventType: 'supplier_order.email_draft_prepared',
                user: $user,
                order: $order,
                oldValues: $oldValues,
                newValues: [
                    'status' => $order->status,
                    'email_message_id' => $emailMessage->id,
                    'to' => $draft['to'],
                ],
            );

            return $emailMessage->load('attachments');
        });
    }

    public function approveDraft(SupplierOrder $order, User $user): EmailMessage
    {
        return DB::transaction(function () use ($order, $user): EmailMessage {
            $emailMessage = $this->latestDraftFor($order);
            $oldEmailValues = $emailMessage->only(['status']);

            $emailMessage->forceFill([
                'status' => 'approved',
            ])->save();

            $oldOrderValues = $order->only(['status']);

            $order->forceFill([
                'status' => SupplierOrderStatus::Approved,
            ])->save();

            $this->writeAuditLog(
                eventType: 'supplier_order.email_approved',
                user: $user,
                order: $order,
                oldValues: [
                    'order' => $oldOrderValues,
                    'email' => $oldEmailValues,
                ],
                newValues: [
                    'order' => $order->only(['status']),
                    'email' => $emailMessage->only(['status']),
                ],
            );

            return $emailMessage;
        });
    }

    private function latestDraftFor(SupplierOrder $order): EmailMessage
    {
        $emailMessage = $order->emailMessages()
            ->where('direction', EmailDirection::Outbound->value)
            ->where('status', 'draft')
            ->latest('id')
            ->first();

        if (! $emailMessage instanceof EmailMessage) {
            throw ValidationException::withMessages([
                'email' => 'No draft supplier order email is available for approval.',
            ]);
        }

        return $emailMessage;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function attachmentPayloads(SupplierOrder $order): array
    {
        return ExportFile::query()
            ->select(['id', 'filename', 'stored_path', 'mime_type'])
            ->where('related_model_type', $order::class)
            ->where('related_model_id', $order->id)
            ->where('status', 'ready')
            ->orderByDesc('id')
            ->get()
            ->map(fn (ExportFile $exportFile): array => [
                'filename' => $exportFile->filename,
                'stored_path' => $exportFile->stored_path,
                'mime_type' => $exportFile->mime_type,
                'size_bytes' => Storage::exists($exportFile->stored_path)
                    ? Storage::size($exportFile->stored_path)
                    : null,
                'checksum' => Storage::exists($exportFile->stored_path)
                    ? hash('sha256', Storage::get($exportFile->stored_path))
                    : null,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    private function writeAuditLog(string $eventType, User $user, SupplierOrder $order, array $oldValues, array $newValues): void
    {
        AuditLog::query()->create([
            'company_id' => $order->company_id,
            'user_id' => $user->id,
            'event_type' => $eventType,
            'auditable_type' => $order::class,
            'auditable_id' => $order->id,
            'old_values_json' => $oldValues,
            'new_values_json' => $newValues,
            'metadata_json' => [],
            'created_at' => now(),
        ]);
    }
}
