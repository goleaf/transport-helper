<?php

namespace App\Services\Supply;

class LogisticsGoogleSheetsSyncService
{
    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function sync(array $options = []): array
    {
        return app(Logistics\LogisticsGoogleSheetsSyncService::class)->sync($options);
    }
}
