<?php

namespace App\Services\Email\Senders;

use App\Contracts\Email\EmailSenderInterface;
use App\Exceptions\NotConfiguredYetException;
use App\Models\EmailAccount;

class GmailEmailSenderPlaceholder implements EmailSenderInterface
{
    public function send(?EmailAccount $account, array $message): array
    {
        throw NotConfiguredYetException::forAdapter('gmail_email_sender');
    }
}
