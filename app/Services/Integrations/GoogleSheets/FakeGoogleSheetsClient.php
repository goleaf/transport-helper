<?php

namespace App\Services\Integrations\GoogleSheets;

use App\Contracts\Integrations\GoogleSheetsClientInterface;

class FakeGoogleSheetsClient implements GoogleSheetsClientInterface
{
    /**
     * @param  array<string, mixed>  $config
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    public function writeRows(array $config, array $rows): array
    {
        return [
            'written_rows' => count($rows),
            'provider' => 'fake_google_sheets',
            'real_call_performed' => false,
        ];
    }
}
