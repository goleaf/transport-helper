<?php

namespace App\Services\Supply\SupplierOrders;

use App\Contracts\Email\EmailSenderInterface;
use App\Enums\EmailDirection;
use App\Enums\LogisticsStatus;
use App\Enums\SupplierOrderStatus;
use App\Models\EmailAccount;
use App\Models\EmailMessage;
use App\Models\SupplierOrder;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\Email\Senders\GmailEmailSenderPlaceholder;
use App\Services\Email\Senders\LogEmailSender;
use App\Services\Email\Senders\MicrosoftGraphEmailSenderPlaceholder;
use App\Services\Email\Senders\SmtpEmailSenderPlaceholder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class SupplierOrderSendService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly LogEmailSender $logEmailSender,
        private readonly SmtpEmailSenderPlaceholder $smtpEmailSender,
        private readonly GmailEmailSenderPlaceholder $gmailEmailSender,
        private readonly MicrosoftGraphEmailSenderPlaceholder $microsoftGraphEmailSender,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function send(SupplierOrder $order, array $options, User $user): array
    {
        return DB::transaction(function () use ($order, $options, $user): array {
            $email = $this->emailForOrder($order);
            $email->load('attachments');

            $this->validateSendState($order, $email, $options);

            $account = $this->resolveAccount($order, $email, $options);
            $senderName = (string) ($options['sender'] ?? config('supply.email.default_sender', 'log'));
            $sender = $this->resolveSender($senderName);
            $message = $this->messagePayload($order, $email);

            try {
                $sendResult = $sender->send($account, $message);
            } catch (Throwable $exception) {
                $this->auditLogService->logDecision('supplier_email_send_failed', $order, $user, [
                    'supplier_order_id' => $order->id,
                    'email_message_id' => $email->id,
                    'sender' => $senderName,
                    'error' => $exception->getMessage(),
                ]);

                throw $exception;
            }

            if (($sendResult['sent'] ?? false) !== true) {
                $this->auditLogService->logDecision('supplier_email_send_failed', $order, $user, [
                    'supplier_order_id' => $order->id,
                    'email_message_id' => $email->id,
                    'sender' => $senderName,
                    'result' => $sendResult,
                ]);

                throw ValidationException::withMessages([
                    'sender' => 'Email sender did not confirm the message was sent.',
                ]);
            }

            $oldOrderStatus = $this->statusValue($order->status);
            $sentAt = now();
            $email->forceFill([
                'status' => 'sent',
                'message_id' => $sendResult['message_id'] ?? $email->message_id,
                'sent_at' => $sentAt,
                'raw_headers_json' => array_merge($email->raw_headers_json ?? [], [
                    'sender' => $senderName,
                    'provider' => $sendResult['provider'] ?? $senderName,
                    'raw_response' => $sendResult['raw_response'] ?? [],
                ]),
            ])->save();

            $order->forceFill([
                'status' => SupplierOrderStatus::Sent,
                'sent_by_user_id' => $user->id,
                'sent_at' => $sentAt,
                'email_message_id' => (string) $email->id,
            ])->save();

            foreach ($order->logisticsRecords()->where('status', LogisticsStatus::Planned->value)->get() as $record) {
                $record->forceFill(['status' => LogisticsStatus::OrderSent])->save();
                $this->auditLogService->logStatusChanged(
                    $record,
                    LogisticsStatus::Planned->value,
                    LogisticsStatus::OrderSent->value,
                    $user,
                    ['supplier_order_id' => $order->id],
                );
            }

            $metadata = [
                'supplier_order_id' => $order->id,
                'email_message_id' => $email->id,
                'message_id' => $email->message_id,
                'provider' => $sendResult['provider'] ?? $senderName,
                'sent_at' => $email->sent_at?->toISOString(),
                'attachment_count' => $email->attachments->count(),
                'recipient_count' => count($email->to_json ?? []),
            ];

            $this->auditLogService->logDecision('supplier_email_sent', $order, $user, $metadata);
            $this->auditLogService->logStatusChanged($order, $oldOrderStatus, SupplierOrderStatus::Sent->value, $user, $metadata);
            $this->auditLogService->write('supplier_order.email_sent', $order, $user, ['status' => $oldOrderStatus], [
                'status' => SupplierOrderStatus::Sent->value,
                'email_message_id' => $email->id,
            ], $metadata, $order->company_id);

            return [
                'supplier_order' => $order->fresh(),
                'email_message' => $email->fresh('attachments'),
                'send_result' => $sendResult,
            ];
        });
    }

    protected function validateSendState(SupplierOrder $order, EmailMessage $email, array $options): void
    {
        if ($this->statusValue($order->status) !== SupplierOrderStatus::Approved->value) {
            throw ValidationException::withMessages([
                'supplier_order' => 'Supplier order email must be approved before it can be sent.',
            ]);
        }

        if ($email->status !== 'approved') {
            throw ValidationException::withMessages([
                'email' => 'Outbound email must be approved before it can be sent.',
            ]);
        }

        if ($email->sent_at !== null && ($options['resend'] ?? false) !== true) {
            throw ValidationException::withMessages([
                'resend' => 'Supplier order email has already been sent.',
            ]);
        }

        if (($email->to_json ?? []) === []) {
            throw ValidationException::withMessages(['to' => 'Email sending requires at least one recipient.']);
        }

        if (! filled($email->subject)) {
            throw ValidationException::withMessages(['subject' => 'Email sending requires a subject.']);
        }

        if (! filled($email->body_text)) {
            throw ValidationException::withMessages(['body_text' => 'Email sending requires a body.']);
        }

        if ($email->attachments->isEmpty() && ! $order->no_attachment_confirmed) {
            throw ValidationException::withMessages([
                'confirm_no_attachment' => 'Email sending requires an attachment or explicit no-attachment confirmation.',
            ]);
        }
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

    protected function resolveAccount(SupplierOrder $order, EmailMessage $email, array $options): ?EmailAccount
    {
        if (! empty($options['email_account_id'])) {
            return EmailAccount::query()
                ->whereKey($options['email_account_id'])
                ->where('company_id', $order->company_id)
                ->first();
        }

        if ($email->email_account_id !== null) {
            return $email->emailAccount;
        }

        return EmailAccount::query()
            ->where('company_id', $order->company_id)
            ->where('is_active', true)
            ->orderBy('id')
            ->first();
    }

    protected function resolveSender(string $sender): EmailSenderInterface
    {
        return match ($sender) {
            'smtp' => $this->smtpEmailSender,
            'gmail' => $this->gmailEmailSender,
            'microsoft_graph' => $this->microsoftGraphEmailSender,
            default => $this->logEmailSender,
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function messagePayload(SupplierOrder $order, EmailMessage $email): array
    {
        return [
            'from' => $email->from_email,
            'to' => $email->to_json ?? [],
            'cc' => $email->cc_json ?? [],
            'subject' => $email->subject,
            'body_text' => $email->body_text,
            'body_html' => $email->body_html,
            'attachments' => $email->attachments
                ->map(fn ($attachment): array => [
                    'filename' => $attachment->original_filename,
                    'stored_path' => $attachment->stored_path,
                    'mime_type' => $attachment->mime_type,
                ])
                ->values()
                ->all(),
            'metadata' => [
                'supplier_order_id' => $order->id,
                'email_message_id' => $email->id,
            ],
        ];
    }

    protected function statusValue(mixed $status): ?string
    {
        return $status instanceof \BackedEnum ? (string) $status->value : ($status === null ? null : (string) $status);
    }
}
