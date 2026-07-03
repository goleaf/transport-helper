<?php

namespace App\Services\Email\Providers;

use App\Contracts\Email\EmailProviderInterface;
use App\Contracts\Email\EmailSenderInterface;
use App\Exceptions\NotConfiguredYetException;
use App\Models\EmailAccount;

class GmailEmailProvider implements EmailProviderInterface, EmailSenderInterface
{
    public function fetchNewMessages(EmailAccount $account, array $options = []): array
    {
        throw new NotConfiguredYetException('Gmail email provider is not configured yet.');
    }

    public function send(?EmailAccount $account, array $message): array
    {
        throw NotConfiguredYetException::forAdapter('gmail_email_sender');
    }
}
