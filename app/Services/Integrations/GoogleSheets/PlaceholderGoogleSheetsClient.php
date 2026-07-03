<?php

namespace App\Services\Integrations\GoogleSheets;

use App\Contracts\Integrations\GoogleSheetsClientInterface;
use App\Exceptions\NotConfiguredYetException;

class PlaceholderGoogleSheetsClient implements GoogleSheetsClientInterface
{
    /**
     * @param  array<string, mixed>  $config
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    public function writeRows(array $config, array $rows): array
    {
        throw NotConfiguredYetException::forAdapter('google_sheets_logistics_sync');
    }
}
