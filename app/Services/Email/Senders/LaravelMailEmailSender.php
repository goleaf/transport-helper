<?php

namespace App\Services\Email\Senders;

use App\Contracts\Email\EmailSenderInterface;
use App\Exceptions\NotConfiguredYetException;
use App\Models\EmailAccount;

class LaravelMailEmailSender implements EmailSenderInterface
{
    public function send(?EmailAccount $account, array $message): array
    {
        throw NotConfiguredYetException::forAdapter('laravel_mail_email_sender');
    }
}
