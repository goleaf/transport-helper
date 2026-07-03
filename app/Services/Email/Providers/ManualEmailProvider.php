<?php

namespace App\Services\Email\Providers;

use App\Contracts\Email\EmailProviderInterface;
use App\Contracts\Email\EmailSenderInterface;
use App\Models\EmailAccount;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ManualEmailProvider implements EmailProviderInterface, EmailSenderInterface
{
    /**
     * @param  array<string, mixed>  $options
     * @return list<array<string, mixed>>
     */
    public function fetchMessages(?EmailAccount $account, array $options = []): array
    {
        $configuredMessages = $account?->encrypted_config['manual_messages'] ?? [];
        $optionMessages = $options['messages'] ?? [];

        return collect(array_merge(
            is_array($configuredMessages) ? $configuredMessages : [],
            is_array($optionMessages) ? $optionMessages : [],
        ))
            ->filter(fn (mixed $message): bool => is_array($message))
            ->map(fn (array $message): array => $this->normalizeMessage($message))
            ->values()
            ->all();
    }

    /**
     * Backward-compatible alias for earlier internal callers.
     *
     * @param  array<string, mixed>  $options
     * @return list<array<string, mixed>>
     */
    public function fetchNewMessages(EmailAccount $account, array $options = []): array
    {
        return $this->fetchMessages($account, $options);
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

    /**
     * @param  array<string, mixed>  $message
     * @return array<string, mixed>
     */
    public function normalizeMessage(array $message): array
    {
        $receivedAt = Arr::get($message, 'received_at') ?: now()->toDateTimeString();
        $messageId = Arr::get($message, 'message_id');

        if (! is_string($messageId) || $messageId === '') {
            $messageId = 'manual-'.hash('sha256', implode('|', [
                (string) Arr::get($message, 'from_email'),
                (string) Arr::get($message, 'subject'),
                (string) Arr::get($message, 'body_text'),
                (string) $receivedAt,
                Str::uuid()->toString(),
            ]));
        }

        return [
            'message_id' => $messageId,
            'thread_id' => Arr::get($message, 'thread_id'),
            'from_email' => is_string(Arr::get($message, 'from_email')) ? Arr::get($message, 'from_email') : null,
            'to' => $this->normalizeAddressList(Arr::get($message, 'to', [])),
            'cc' => $this->normalizeAddressList(Arr::get($message, 'cc', [])),
            'subject' => is_string(Arr::get($message, 'subject')) ? Arr::get($message, 'subject') : null,
            'body_text' => is_string(Arr::get($message, 'body_text')) ? Arr::get($message, 'body_text') : null,
            'body_html' => is_string(Arr::get($message, 'body_html')) ? Arr::get($message, 'body_html') : null,
            'received_at' => $receivedAt,
            'raw_headers' => is_array(Arr::get($message, 'raw_headers')) ? Arr::get($message, 'raw_headers') : [],
            'attachments' => is_array(Arr::get($message, 'attachments')) ? Arr::get($message, 'attachments') : [],
        ];
    }

    /**
     * @return list<string>
     */
    private function normalizeAddressList(mixed $value): array
    {
        if (is_string($value) && $value !== '') {
            return [$value];
        }

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, fn (mixed $item): bool => is_string($item) && $item !== ''));
    }
}
