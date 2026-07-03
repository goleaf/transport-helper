<?php

namespace App\Imports\Adapters;

use App\Contracts\Import\ImportAdapterInterface;
use App\Exceptions\NotConfiguredYetException;

class ManualJsonImportAdapter implements ImportAdapterInterface
{
    public function rows(string $sourcePath, array $options = []): array
    {
        throw NotConfiguredYetException::forAdapter('manual_json');
    }

    public function checksum(string $sourcePath): string
    {
        throw NotConfiguredYetException::forAdapter('manual_json');
    }
}
