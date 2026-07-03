<?php

namespace App\Services\Supply;

use App\Enums\EmailDirection;
use App\Enums\SupplierOrderStatus;
use App\Models\AuditLog;
use App\Models\EmailMessage;
use App\Models\SupplierOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupplierOrderSendService
{
    /**
     * @param  array<string, mixed>  $options
     */
    public function send(SupplierOrder $order, User $user, array $options = []): EmailMessage
    {
        return DB::transaction(function () use ($order, $user, $options): EmailMessage {
            $approvedEmail = $this->latestApprovedEmailFor($order);
            $approvedEmail->load('attachments');

            $hasAttachments = $approvedEmail->attachments->isNotEmpty();
            $noAttachmentConfirmed = (bool) ($options['no_attachment_confirmed'] ?? false);

            if (! $hasAttachments && ! $noAttachmentConfirmed) {
                throw ValidationException::withMessages([
                    'no_attachment_confirmed' => 'Supplier order email cannot be sent without an attachment unless no_attachment_confirmed is explicitly accepted.',
                ]);
            }

            $messageId = $this->manualProviderMessageId($order, $approvedEmail);
            $sentEmail = EmailMessage::query()->create([
                'company_id' => $approvedEmail->company_id,
                'email_account_id' => $approvedEmail->email_account_id,
                'direction' => EmailDirection::Outbound,
                'message_id' => $messageId,
                'thread_id' => $approvedEmail->thread_id,
                'from_email' => $approvedEmail->from_email,
                'to_json' => $approvedEmail->to_json,
                'cc_json' => $approvedEmail->cc_json,
                'subject' => $approvedEmail->subject,
                'body_text' => $approvedEmail->body_text,
                'body_html' => $approvedEmail->body_html,
                'sent_at' => now(),
                'related_supplier_id' => $approvedEmail->related_supplier_id,
                'related_supplier_order_id' => $order->id,
                'status' => 'sent',
                'raw_headers_json' => [
                    'provider' => 'manual',
                    'approved_email_message_id' => $approvedEmail->id,
                ],
            ]);

            foreach ($approvedEmail->attachments as $attachment) {
                $sentEmail->attachments()->create([
                    'original_filename' => $attachment->original_filename,
                    'stored_path' => $attachment->stored_path,
                    'mime_type' => $attachment->mime_type,
                    'size_bytes' => $attachment->size_bytes,
                    'checksum' => $attachment->checksum,
                ]);
            }

            $oldOrderValues = $order->only(['status', 'sent_by_user_id', 'sent_at', 'email_message_id']);

            $order->forceFill([
                'status' => SupplierOrderStatus::Sent,
                'sent_by_user_id' => $user->id,
                'sent_at' => now(),
                'email_message_id' => $messageId,
            ])->save();

            AuditLog::query()->create([
                'company_id' => $order->company_id,
                'user_id' => $user->id,
                'event_type' => 'supplier_order.email_sent',
                'auditable_type' => $order::class,
                'auditable_id' => $order->id,
                'old_values_json' => $oldOrderValues,
                'new_values_json' => [
                    'status' => $order->status,
                    'sent_by_user_id' => $order->sent_by_user_id,
                    'sent_at' => $order->sent_at,
                    'email_message_id' => $order->email_message_id,
                    'email_message_record_id' => $sentEmail->id,
                ],
                'metadata_json' => [
                    'no_attachment_confirmed' => $noAttachmentConfirmed,
                    'attachments_count' => $sentEmail->attachments()->count(),
                ],
                'created_at' => now(),
            ]);

            return $sentEmail->load('attachments');
        });
    }

    private function latestApprovedEmailFor(SupplierOrder $order): EmailMessage
    {
        $emailMessage = $order->emailMessages()
            ->where('direction', EmailDirection::Outbound->value)
            ->where('status', 'approved')
            ->latest('id')
            ->first();

        if (! $emailMessage instanceof EmailMessage) {
            throw ValidationException::withMessages([
                'email' => 'Supplier order email must be approved before it can be sent.',
            ]);
        }

        return $emailMessage;
    }

    private function manualProviderMessageId(SupplierOrder $order, EmailMessage $approvedEmail): string
    {
        return sprintf(
            'manual-%s-%s-%s',
            $order->id,
            $approvedEmail->id,
            now()->format('YmdHis'),
        );
    }
}
