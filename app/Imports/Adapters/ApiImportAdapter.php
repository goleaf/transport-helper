<?php

namespace App\Imports\Adapters;

use App\Contracts\Import\ImportAdapterInterface;
use App\Exceptions\NotConfiguredYetException;

class ApiImportAdapter implements ImportAdapterInterface
{
    public function rows(string $sourcePath, array $options = []): array
    {
        throw NotConfiguredYetException::forAdapter('api');
    }

    public function checksum(string $sourcePath): string
    {
        throw NotConfiguredYetException::forAdapter('api');
    }
}
