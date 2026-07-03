<?php

namespace App\Imports\Adapters;

use App\Contracts\Import\ImportAdapterInterface;
use App\Exceptions\NotConfiguredYetException;

class GoogleSheetsImportAdapter implements ImportAdapterInterface
{
    public function rows(string $sourcePath, array $options = []): array
    {
        throw NotConfiguredYetException::forAdapter('google_sheets');
    }

    public function checksum(string $sourcePath): string
    {
        throw NotConfiguredYetException::forAdapter('google_sheets');
    }
}
