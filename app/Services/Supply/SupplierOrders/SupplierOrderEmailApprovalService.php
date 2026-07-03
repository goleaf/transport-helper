<?php

namespace App\Services\Supply\SupplierOrders;

use App\Enums\EmailDirection;
use App\Enums\SupplierOrderStatus;
use App\Models\EmailMessage;
use App\Models\SupplierOrder;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupplierOrderEmailApprovalService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public function approveEmail(SupplierOrder $order, array $validated, User $user): array
    {
        return DB::transaction(function () use ($order, $validated, $user): array {
            $email = $this->emailForOrder($order);
            $email->load('attachments');

            if ($this->statusValue($order->status) !== SupplierOrderStatus::EmailPrepared->value) {
                throw ValidationException::withMessages([
                    'supplier_order' => 'Supplier order email can only be approved after a draft is prepared.',
                ]);
            }

            if ($this->statusValue($email->direction) !== EmailDirection::Outbound->value) {
                throw ValidationException::withMessages([
                    'email' => 'Only outbound supplier order emails can be approved.',
                ]);
            }

            if ($email->to_json === [] || $email->to_json === null) {
                throw ValidationException::withMessages(['to' => 'Email approval requires at least one recipient.']);
            }

            if (! filled($email->subject)) {
                throw ValidationException::withMessages(['subject' => 'Email approval requires a subject.']);
            }

            if (! filled($email->body_text)) {
                throw ValidationException::withMessages(['body_text' => 'Email approval requires a body.']);
            }

            $noAttachmentConfirmed = (bool) ($validated['confirm_no_attachment'] ?? false);

            if ($email->attachments->isEmpty() && ! $noAttachmentConfirmed) {
                throw ValidationException::withMessages([
                    'confirm_no_attachment' => 'Email approval requires an attachment or explicit no-attachment confirmation.',
                ]);
            }

            $oldStatus = $this->statusValue($order->status);
            $email->forceFill(['status' => 'approved'])->save();
            $order->forceFill([
                'status' => SupplierOrderStatus::Approved,
                'email_approved_at' => now(),
                'email_approved_by_user_id' => $user->id,
                'no_attachment_confirmed' => $noAttachmentConfirmed || $order->no_attachment_confirmed,
            ])->save();

            $metadata = [
                'supplier_order_id' => $order->id,
                'email_message_id' => $email->id,
                'approved_by_user_id' => $user->id,
                'no_attachment_confirmed' => $noAttachmentConfirmed,
                'approval_note' => $validated['approval_note'] ?? null,
            ];

            $this->auditLogService->logDecision('supplier_email_approved', $order, $user, $metadata);
            $this->auditLogService->logStatusChanged($order, $oldStatus, SupplierOrderStatus::Approved->value, $user, $metadata);

            return [
                'supplier_order' => $order->fresh(),
                'email_message' => $email->fresh('attachments'),
            ];
        });
    }

    protected function emailForOrder(SupplierOrder $order): EmailMessage
    {
        if (is_string($order->email_message_id) && ctype_digit($order->email_message_id)) {
            $email = EmailMessage::query()
                ->whereKey((int) $order->email_message_id)
                ->first();

            if ($email instanceof EmailMessage) {
                return $email;
            }
        }

        $email = $order->emailMessages()
            ->where('direction', EmailDirection::Outbound->value)
            ->latest('id')
            ->first();

        if (! $email instanceof EmailMessage) {
            throw ValidationException::withMessages([
                'email' => 'No outbound email is available for this supplier order.',
            ]);
        }

        return $email;
    }

    protected function statusValue(mixed $status): ?string
    {
        return $status instanceof \BackedEnum ? (string) $status->value : ($status === null ? null : (string) $status);
    }
}
