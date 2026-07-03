<?php

namespace App\Services\Email\Providers;

use App\Contracts\Email\EmailProviderInterface;
use App\Contracts\Email\EmailSenderInterface;
use App\Exceptions\NotConfiguredYetException;
use App\Models\EmailAccount;

class MicrosoftGraphEmailProvider implements EmailProviderInterface, EmailSenderInterface
{
    public function fetchNewMessages(EmailAccount $account, array $options = []): array
    {
        throw new NotConfiguredYetException('Microsoft Graph email provider is not configured yet.');
    }

    public function send(EmailAccount $account, array $message): array
    {
        throw new NotConfiguredYetException('Microsoft Graph email sender is not configured yet.');
    }
}
