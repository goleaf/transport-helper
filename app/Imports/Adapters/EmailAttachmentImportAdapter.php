<?php

namespace App\Imports\Adapters;

use App\Contracts\Import\ImportAdapterInterface;
use App\Exceptions\NotConfiguredYetException;

class EmailAttachmentImportAdapter implements ImportAdapterInterface
{
    public function rows(string $sourcePath, array $options = []): array
    {
        throw NotConfiguredYetException::forAdapter('email_attachment');
    }

    public function checksum(string $sourcePath): string
    {
        throw NotConfiguredYetException::forAdapter('email_attachment');
    }
}
