<?php

namespace App\Services\Email\Providers;

use App\Contracts\Email\EmailProviderInterface;
use App\Exceptions\NotConfiguredYetException;
use App\Models\EmailAccount;

class GmailEmailProviderPlaceholder implements EmailProviderInterface
{
    /**
     * @param  array<string, mixed>  $options
     * @return list<array<string, mixed>>
     */
    public function fetchMessages(?EmailAccount $account, array $options = []): array
    {
        throw NotConfiguredYetException::forAdapter('gmail_email_provider');
    }

    /**
     * @param  array<string, mixed>  $options
     * @return list<array<string, mixed>>
     */
    public function fetchNewMessages(EmailAccount $account, array $options = []): array
    {
        return $this->fetchMessages($account, $options);
    }
}
