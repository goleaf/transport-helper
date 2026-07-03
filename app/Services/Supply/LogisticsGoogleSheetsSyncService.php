<?php

namespace App\Services\Supply;

use App\Exceptions\NotConfiguredYetException;

class LogisticsGoogleSheetsSyncService
{
    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function sync(array $options = []): array
    {
        throw new NotConfiguredYetException('Google Sheets logistics sync is not configured yet.');
    }
}
