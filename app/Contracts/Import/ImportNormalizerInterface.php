<?php

namespace App\Contracts\Import;

interface ImportNormalizerInterface
{
    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function normalize(array $row, array $context = []): array;
}
