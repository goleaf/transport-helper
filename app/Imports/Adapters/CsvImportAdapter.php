<?php

namespace App\Imports\Adapters;

use App\Contracts\Import\ImportAdapterInterface;
use RuntimeException;
use SplFileObject;

class CsvImportAdapter implements ImportAdapterInterface
{
    /**
     * @param  array<string, mixed>  $options
     * @return list<array{row_number:int,data:array<string,mixed>}>
     */
    public function rows(string $sourcePath, array $options = []): array
    {
        if (! is_readable($sourcePath)) {
            throw new RuntimeException("Import source [{$sourcePath}] is not readable.");
        }

        $delimiter = $options['delimiter'] ?? $this->detectDelimiter($sourcePath);
        $file = new SplFileObject($sourcePath);
        $file->setCsvControl((string) $delimiter);
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);

        $headers = [];
        $rows = [];

        foreach ($file as $index => $columns) {
            if (! is_array($columns) || $columns === [null]) {
                continue;
            }

            $columns = array_map(fn ($value) => is_string($value) ? trim($value) : $value, $columns);

            if ($headers === []) {
                $headers = $this->headers($columns);

                continue;
            }

            if ($this->isBlankRow($columns)) {
                continue;
            }

            $rows[] = [
                'row_number' => $index + 1,
                'data' => $this->combine($headers, $columns),
            ];
        }

        return $rows;
    }

    public function checksum(string $sourcePath): string
    {
        $checksum = hash_file('sha256', $sourcePath);

        if ($checksum === false) {
            throw new RuntimeException("Could not calculate checksum for [{$sourcePath}].");
        }

        return $checksum;
    }

    /**
     * @param  list<mixed>  $columns
     * @return list<string>
     */
    private function headers(array $columns): array
    {
        return array_map(function ($header): string {
            $normalized = strtolower(trim((string) $header));
            $normalized = preg_replace('/[^a-z0-9]+/', '_', $normalized) ?? $normalized;

            return trim($normalized, '_');
        }, $columns);
    }

    /**
     * @param  list<string>  $headers
     * @param  list<mixed>  $columns
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
     * @param  list<mixed>  $columns
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

    private function detectDelimiter(string $sourcePath): string
    {
        $handle = fopen($sourcePath, 'rb');

        if ($handle === false) {
            throw new RuntimeException("Cannot open [{$sourcePath}].");
        }

        $line = fgets($handle);
        fclose($handle);

        $candidates = [',', ';', "\t", '|'];
        $scores = [];

        foreach ($candidates as $candidate) {
            $scores[$candidate] = substr_count((string) $line, $candidate);
        }

        arsort($scores);

        return (string) array_key_first($scores);
    }
}
