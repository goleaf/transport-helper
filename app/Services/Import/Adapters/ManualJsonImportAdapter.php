<?php

namespace App\Services\Import\Adapters;

use App\Contracts\Import\ImportAdapterInterface;
use RuntimeException;

class ManualJsonImportAdapter implements ImportAdapterInterface
{
    /**
     * @param  array<string, mixed>  $config
     * @return array<int, array<string, mixed>>
     */
    public function read(array $config): array
    {
        $rows = $config['rows'] ?? null;

        if (! is_array($rows)) {
            throw new RuntimeException('Manual JSON import requires a rows array.');
        }

        return array_values(array_filter($rows, fn (mixed $row): bool => is_array($row)));
    }
}
