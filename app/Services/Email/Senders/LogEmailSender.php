<?php

namespace App\Services\Email\Senders;

use App\Contracts\Email\EmailSenderInterface;
use App\Models\EmailAccount;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LogEmailSender implements EmailSenderInterface
{
    public function send(?EmailAccount $account, array $message): array
    {
        $messageId = 'log-'.(string) Str::uuid();

        Log::info('Supplier order email logged instead of sent.', [
            'message_id' => $messageId,
            'email_account_id' => $account?->id,
            'to' => $message['to'] ?? [],
            'cc' => $message['cc'] ?? [],
            'subject' => $message['subject'] ?? null,
            'attachments_count' => count($message['attachments'] ?? []),
            'metadata' => $message['metadata'] ?? [],
        ]);

        return [
            'sent' => true,
            'message_id' => $messageId,
            'provider' => 'log',
            'sent_at' => now()->toISOString(),
            'raw_response' => [
                'mode' => 'log_only',
            ],
        ];
    }
}
