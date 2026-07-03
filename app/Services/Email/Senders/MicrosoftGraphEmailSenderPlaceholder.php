<?php

namespace App\Services\Email\Senders;

use App\Contracts\Email\EmailSenderInterface;
use App\Exceptions\NotConfiguredYetException;
use App\Models\EmailAccount;

class MicrosoftGraphEmailSenderPlaceholder implements EmailSenderInterface
{
    public function send(?EmailAccount $account, array $message): array
    {
        throw NotConfiguredYetException::forAdapter('microsoft_graph_email_sender');
    }
}
