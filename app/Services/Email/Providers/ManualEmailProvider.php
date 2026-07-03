<?php

namespace App\Services\Email\Providers;

use App\Contracts\Email\EmailProviderInterface;
use App\Contracts\Email\EmailSenderInterface;
use App\Models\EmailAccount;

class ManualEmailProvider implements EmailProviderInterface, EmailSenderInterface
{
    public function fetchNewMessages(EmailAccount $account, array $options = []): array
    {
        $configuredMessages = $account->encrypted_config['manual_messages'] ?? [];
        $optionMessages = $options['messages'] ?? [];

        return array_values(array_merge(
            is_array($configuredMessages) ? $configuredMessages : [],
            is_array($optionMessages) ? $optionMessages : [],
        ));
    }

    public function send(?EmailAccount $account, array $message): array
    {
        $messageId = $message['message_id'] ?? sprintf(
            'manual-%s-%s',
            $account?->id ?? 'no-account',
            hash('sha256', (string) json_encode($message)),
        );

        return [
            'sent' => true,
            'message_id' => $messageId,
            'provider' => 'manual',
            'sent_at' => now()->toISOString(),
            'raw_response' => [
                'mode' => 'manual_provider',
            ],
        ];
    }
}
