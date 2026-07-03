<?php

namespace App\Contracts\Import;

interface ImportValidatorInterface
{
    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>  $context
     * @return array{valid:bool,errors:list<string>,warnings:list<string>,normalized:array<string,mixed>}
     */
    public function validate(array $row, array $context = []): array;
}
