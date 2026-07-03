<?php

namespace App\Contracts\Import;

interface ImportAdapterInterface
{
    /**
     * @param  array<string, mixed>  $config
     * @return array<int, array<string, mixed>>
     */
    public function read(array $config): array;
}
