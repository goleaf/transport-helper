<?php

namespace App\Adapters;

use App\Contracts\IncomingEmailAdapter;

class ArrayIncomingEmailAdapter implements IncomingEmailAdapter
{
    /**
     * @param  array<int, array<string, mixed>>  $emails
     */
    public function __construct(private array $emails) {}

    public function pendingEmails(): array
    {
        return $this->emails;
    }
}
