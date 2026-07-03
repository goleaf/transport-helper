<?php

namespace App\Services\Import\Normalizers\Concerns;

trait ResolvesImportAliases
{
    /**
     * @param  array<string, mixed>  $row
     * @param  list<string>  $aliases
     */
    private function firstValue(array $row, array $aliases): mixed
    {
        foreach ($aliases as $alias) {
            if (array_key_exists($alias, $row)) {
                return $row[$alias];
            }
        }

        return null;
    }
}
