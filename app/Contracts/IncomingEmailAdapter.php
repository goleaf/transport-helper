<?php

namespace App\Contracts;

interface IncomingEmailAdapter
{
    /**
     * @return array<int, array{
     *     from_email: string,
     *     subject: string,
     *     body: string,
     *     received_at: string,
     *     message_id?: string
     * }>
     */
    public function pendingEmails(): array;
}
