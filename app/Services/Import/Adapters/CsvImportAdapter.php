<?php

namespace App\Services\Import\Adapters;

use App\Contracts\Import\ImportAdapterInterface;
use RuntimeException;

class CsvImportAdapter implements ImportAdapterInterface
{
    /**
     * @param  array<string, mixed>  $config
     * @return array<int, array<string, mixed>>
     */
    public function read(array $config): array
    {
        $filePath = (string) ($config['file_path'] ?? '');

        if ($filePath === '' || ! is_readable($filePath)) {
            throw new RuntimeException("Import source [{$filePath}] is not readable.");
        }

        $delimiter = (string) ($config['delimiter'] ?? ',');
        $enclosure = (string) ($config['enclosure'] ?? '"');
        $escape = (string) ($config['escape'] ?? '\\');
        $hasHeader = (bool) ($config['has_header'] ?? true);
        $headerMap = $this->normalizedHeaderMap($config['header_map'] ?? []);
        $handle = fopen($filePath, 'rb');

        if ($handle === false) {
            throw new RuntimeException("Cannot open import source [{$filePath}].");
        }

        $headers = [];
        $rows = [];

        try {
            while (($columns = fgetcsv($handle, 0, $delimiter, $enclosure, $escape)) !== false) {
                $columns = $this->trimColumns($columns);

                if ($this->isBlankRow($columns)) {
                    continue;
                }

                if ($hasHeader && $headers === []) {
                    $headers = $this->headers($columns, $headerMap);

                    continue;
                }

                $rows[] = $hasHeader
                    ? $this->combine($headers, $columns)
                    : $this->withoutHeader($columns);
            }
        } finally {
            fclose($handle);
        }

        return $rows;
    }

    /**
     * @param  array<int, mixed>  $columns
     * @return array<int, mixed>
     */
    private function trimColumns(array $columns): array
    {
        return array_map(fn (mixed $value): mixed => is_string($value) ? trim($value) : $value, $columns);
    }

    /**
     * @param  array<int, mixed>  $columns
     * @param  array<string, string>  $headerMap
     * @return list<string>
     */
    private function headers(array $columns, array $headerMap): array
    {
        return array_values(array_map(function (mixed $header) use ($headerMap): string {
            $normalized = $this->normalizeHeader((string) $header);

            return $headerMap[$normalized] ?? $normalized;
        }, $columns));
    }

    private function normalizeHeader(string $header): string
    {
        $normalized = preg_replace('/^\xEF\xBB\xBF/', '', $header) ?? $header;
        $normalized = strtolower(trim($normalized));
        $normalized = preg_replace('/[^a-z0-9]+/', '_', $normalized) ?? $normalized;

        return trim($normalized, '_');
    }

    /**
     * @return array<string, string>
     */
    private function normalizedHeaderMap(mixed $headerMap): array
    {
        if (! is_array($headerMap)) {
            return [];
        }

        $normalized = [];

        foreach ($headerMap as $from => $to) {
            $normalized[$this->normalizeHeader((string) $from)] = $this->normalizeHeader((string) $to);
        }

        return $normalized;
    }

    /**
     * @param  list<string>  $headers
     * @param  array<int, mixed>  $columns
     * @return array<string, mixed>
     */
    private function combine(array $headers, array $columns): array
    {
        $row = [];

        foreach ($headers as $index => $header) {
            if ($header === '') {
                continue;
            }

            $row[$header] = $columns[$index] ?? null;
        }

        return $row;
    }

    /**
     * @param  array<int, mixed>  $columns
     * @return array<string, mixed>
     */
    private function withoutHeader(array $columns): array
    {
        $row = [];

        foreach ($columns as $index => $value) {
            $row['column_'.($index + 1)] = $value;
        }

        return $row;
    }

    /**
     * @param  array<int, mixed>  $columns
     */
    private function isBlankRow(array $columns): bool
    {
        foreach ($columns as $column) {
            if (trim((string) $column) !== '') {
                return false;
            }
        }

        return true;
    }
}
