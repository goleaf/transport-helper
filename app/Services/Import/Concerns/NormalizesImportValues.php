<?php

namespace App\Services\Import\Concerns;

trait NormalizesImportValues
{
    /**
     * @param  array<string, mixed>  $row
     */
    private function stringValue(array $row, string $key, ?string $default = null): ?string
    {
        $value = $row[$key] ?? $default;

        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? $default : $value;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function numericValue(array $row, string $key): mixed
    {
        $value = $row[$key] ?? null;

        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return str_replace(',', '.', trim((string) $value));
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function booleanValue(array $row, string $key, bool $default = false): bool
    {
        $value = $row[$key] ?? null;

        if ($value === null || trim((string) $value) === '') {
            return $default;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'y'], true);
    }
}
