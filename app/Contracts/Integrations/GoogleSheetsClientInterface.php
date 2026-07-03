<?php

namespace App\Contracts\Integrations;

interface GoogleSheetsClientInterface
{
    /**
     * @param  array<string, mixed>  $config
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    public function writeRows(array $config, array $rows): array;
}
