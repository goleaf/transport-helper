<?php

namespace App\Services\Import\Adapters;

use App\Contracts\Import\ImportAdapterInterface;
use App\Exceptions\NotConfiguredYetException;

class EmailAttachmentImportAdapter implements ImportAdapterInterface
{
    public function read(array $config): array
    {
        throw NotConfiguredYetException::forAdapter('email_attachment');
    }
}
