<?php

namespace App\Contracts\Email;

use App\Models\EmailAccount;

interface EmailProviderInterface
{
    /**
     * @param  array<string, mixed>  $options
     * @return list<array<string, mixed>>
     */
    public function fetchNewMessages(EmailAccount $account, array $options = []): array;
}
