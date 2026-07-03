<?php

namespace App\Services\Import\Adapters;

use App\Contracts\Import\ImportAdapterInterface;
use App\Exceptions\NotConfiguredYetException;

class ApiImportAdapter implements ImportAdapterInterface
{
    public function read(array $config): array
    {
        throw NotConfiguredYetException::forAdapter('api');
    }
}
