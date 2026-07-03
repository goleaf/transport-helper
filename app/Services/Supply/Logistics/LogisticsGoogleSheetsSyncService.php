<?php

namespace App\Services\Supply\Logistics;

use App\Exceptions\NotConfiguredYetException;
use App\Models\User;
use App\Services\Audit\AuditLogService;

class LogisticsGoogleSheetsSyncService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function sync(array $filters = [], ?User $user = null): array
    {
        $this->auditLogService->write('google_sheets_sync_not_configured', null, $user, null, null, [
            'filters' => $filters,
            'adapter' => 'google_sheets_logistics_sync',
        ]);

        throw NotConfiguredYetException::forAdapter('google_sheets_logistics_sync');
    }
}
