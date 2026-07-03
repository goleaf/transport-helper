<?php

namespace App\Contracts\Import;

interface ImportValidatorInterface
{
    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>  $context
     * @return list<string>
     */
    public function validate(array $row, array $context = []): array;
}
