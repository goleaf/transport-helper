<?php

namespace App\Contracts\Email;

use App\Models\EmailAccount;

interface EmailSenderInterface
{
    /**
     * @param  array<string, mixed>  $message
     * @return array<string, mixed>
     */
    public function send(EmailAccount $account, array $message): array;
}
