<?php

namespace App\Imports\Adapters;

use App\Contracts\Import\ImportAdapterInterface;
use App\Exceptions\NotConfiguredYetException;

class ExcelImportAdapter implements ImportAdapterInterface
{
    public function rows(string $sourcePath, array $options = []): array
    {
        throw NotConfiguredYetException::forAdapter('excel');
    }

    public function checksum(string $sourcePath): string
    {
        throw NotConfiguredYetException::forAdapter('excel');
    }
}
