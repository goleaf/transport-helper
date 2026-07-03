<?php

namespace App\Services\Supply\Logistics;

use App\Contracts\Integrations\GoogleSheetsClientInterface;
use App\Enums\IntegrationApprovalStatus;
use App\Models\IntegrationConnection;
use App\Models\LogisticsRecord;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Validation\ValidationException;

class LogisticsGoogleSheetsSyncService
{
    public function __construct(
        private readonly GoogleSheetsClientInterface $client,
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function sync(array $filters = [], ?User $user = null, array $options = []): array
    {
        $dryRun = (bool) ($options['dry_run'] ?? true);
        $allowRealCall = (bool) ($options['allow_real_call'] ?? false);
        $rows = $this->rows($filters);

        if ($dryRun) {
            $result = [
                'dry_run' => true,
                'row_count' => count($rows),
                'rows' => $rows,
                'provider_result' => null,
            ];

            $this->auditLogService->write('google_sheets_logistics_sync_dry_run', null, $user, null, null, [
                'filters' => $filters,
                'row_count' => count($rows),
            ]);

            return $result;
        }

        $connection = $this->resolveConnection($options);
        $this->guardRealSync($connection, $allowRealCall, (bool) ($options['allow_testing_real_call_with_fake_client'] ?? false));
        $providerResult = $this->client->writeRows($connection->encrypted_config ?? [], $rows);

        $this->auditLogService->write('google_sheets_logistics_synced', $connection, $user, null, null, [
            'filters' => $filters,
            'row_count' => count($rows),
            'provider_result' => $providerResult,
        ], $connection->company_id);

        return [
            'dry_run' => false,
            'row_count' => count($rows),
            'rows' => $rows,
            'provider_result' => $providerResult,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
     */
    private function rows(array $filters): array
    {
        return LogisticsRecord::query()
            ->with(['supplier:id,name', 'supplierOrder:id,order_number', 'carrier:id,name'])
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->orderByDesc('id')
            ->limit(1000)
            ->get()
            ->map(fn (LogisticsRecord $record): array => [
                'logistics_record_id' => $record->id,
                'supplier' => $record->supplier?->name,
                'supplier_order_number' => $record->supplierOrder?->order_number,
                'ready_date' => $record->ready_date?->toDateString(),
                'pickup_date' => $record->pickup_date?->toDateString(),
                'delivery_date' => $record->delivery_date?->toDateString(),
                'actual_received_date' => $record->actual_received_date?->toDateString(),
                'carrier' => $record->carrier?->name,
                'transport_price' => $record->transport_price,
                'currency' => $record->currency,
                'status' => $record->status?->value ?? $record->status,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function resolveConnection(array $options): IntegrationConnection
    {
        if (! empty($options['connection_id'])) {
            return IntegrationConnection::query()->findOrFail($options['connection_id']);
        }

        $connection = IntegrationConnection::query()
            ->where('provider', 'google_sheets')
            ->where('is_active', true)
            ->orderByDesc('id')
            ->first();

        if (! $connection instanceof IntegrationConnection) {
            throw ValidationException::withMessages([
                'connection' => 'Approved Google Sheets integration is required for real sync.',
            ]);
        }

        return $connection;
    }

    private function guardRealSync(IntegrationConnection $connection, bool $allowRealCall, bool $allowTestingFakeClient): void
    {
        if (! $allowRealCall) {
            throw ValidationException::withMessages([
                'allow_real_call' => 'Google Sheets real sync requires explicit allow_real_call.',
            ]);
        }

        if (app()->environment('testing') && ! $allowTestingFakeClient) {
            throw ValidationException::withMessages([
                'environment' => 'Google Sheets real sync is blocked in tests.',
            ]);
        }

        if (! (bool) config('supply.google_sheets.enabled', false) && ! $allowTestingFakeClient) {
            throw ValidationException::withMessages([
                'google_sheets' => 'Google Sheets sync is disabled by configuration.',
            ]);
        }

        if ($connection->approval_status !== IntegrationApprovalStatus::Approved->value) {
            throw ValidationException::withMessages([
                'approval_status' => 'Google Sheets integration must be approved before sync.',
            ]);
        }

        if ($connection->provider !== 'google_sheets') {
            throw ValidationException::withMessages([
                'provider' => 'Connection must use the google_sheets provider.',
            ]);
        }
    }
}
