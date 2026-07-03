<?php

namespace App\Contracts\Import;

interface ImportAdapterInterface
{
    /**
     * @param  array<string, mixed>  $options
     * @return list<array{row_number:int,data:array<string,mixed>}>
     */
    public function rows(string $sourcePath, array $options = []): array;

    public function checksum(string $sourcePath): string;
}
