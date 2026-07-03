<?php

namespace App\Services\Supply\Logistics;

use App\Enums\LogisticsStatus;
use App\Models\LogisticsRecord;
use App\Services\Audit\AuditLogService;

class LogisticsDelayMonitoringService
{
    public function __construct(
        private readonly LogisticsStatusResolver $statusResolver,
        private readonly LogisticsNotificationService $notificationService,
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function monitor(array $options = []): array
    {
        $dryRun = (bool) ($options['dry_run'] ?? false);
        $updateStatus = (bool) ($options['update_status'] ?? config('supply.logistics.auto_update_delayed_status', true));
        $expectedSoonDays = (int) ($options['expected_soon_days'] ?? config('supply.logistics.expected_soon_days', 3));
        $findings = [];
        $delayedCount = 0;
        $expectedSoonCount = 0;
        $missingDataCount = 0;
        $notificationsCreated = 0;

        $records = LogisticsRecord::query()
            ->select([
                'id',
                'company_id',
                'supplier_order_id',
                'supplier_id',
                'carrier_id',
                'supplier_confirmation_id',
                'order_date',
                'confirmation_date',
                'ready_date',
                'pickup_date',
                'delivery_date',
                'actual_received_date',
                'status',
                'receiving_discrepancies_json',
                'last_delay_checked_at',
                'delay_reason',
                'created_at',
            ])
            ->with([
                'supplierOrder:id,status,sent_at,order_number',
                'supplierOrder.items:id,supplier_order_id,product_id,ordered_quantity,confirmed_quantity,received_quantity',
                'supplierConfirmation:id,status,ready_date',
            ])
            ->open()
            ->when(isset($options['company_id']), fn ($query) => $query->where('company_id', $options['company_id']))
            ->orderBy('id')
            ->limit(1000)
            ->get();

        foreach ($records as $record) {
            $suggestion = $this->statusResolver->suggestStatus($record);
            $reasons = $suggestion['reasons'];

            if ($suggestion['suggested_status'] === LogisticsStatus::Delayed->value) {
                $delayedCount++;
            }

            if ($this->isExpectedSoon($record, $expectedSoonDays)) {
                $expectedSoonCount++;
                $reasons[] = 'goods_expected_soon';
            }

            if (in_array('confirmation_exists_ready_date_missing', $reasons, true)) {
                $missingDataCount++;
            }

            if ($reasons === []) {
                continue;
            }

            $findings[] = [
                'logistics_record_id' => $record->id,
                'supplier_order_id' => $record->supplier_order_id,
                'suggested_status' => $suggestion['suggested_status'],
                'reasons' => array_values(array_unique($reasons)),
            ];

            if (! $dryRun) {
                if ($updateStatus && in_array($suggestion['suggested_status'], [LogisticsStatus::Delayed->value, LogisticsStatus::NeedsReview->value], true)) {
                    $record->forceFill([
                        'status' => $suggestion['suggested_status'],
                        'delay_reason' => implode(', ', array_values(array_unique($reasons))),
                    ])->save();
                    $this->auditLogService->write('logistics_delay_detected', $record, null, null, null, [
                        'reasons' => $reasons,
                    ], $record->company_id);
                }

                $record->forceFill(['last_delay_checked_at' => now()])->save();
                $notificationsCreated += $this->notifyForFinding($record, $reasons);
            }
        }

        $summary = [
            'checked_count' => $records->count(),
            'delayed_count' => $delayedCount,
            'expected_soon_count' => $expectedSoonCount,
            'missing_data_count' => $missingDataCount,
            'notifications_created' => $notificationsCreated,
            'dry_run' => $dryRun,
            'findings' => $findings,
        ];

        if (! $dryRun) {
            $this->auditLogService->write('logistics_delay_monitoring_completed', null, null, null, null, $summary);
        }

        return $summary;
    }

    private function isExpectedSoon(LogisticsRecord $record, int $expectedSoonDays): bool
    {
        return $record->delivery_date !== null
            && $record->actual_received_date === null
            && $record->delivery_date->betweenIncluded(now()->startOfDay(), now()->addDays($expectedSoonDays)->endOfDay());
    }

    /**
     * @param  list<string>  $reasons
     */
    private function notifyForFinding(LogisticsRecord $record, array $reasons): int
    {
        $count = 0;

        if (in_array('goods_expected_soon', $reasons, true)) {
            $count += $this->notificationService->notify('goods_expected_soon', [
                'company_id' => $record->company_id,
                'title' => 'Goods expected soon',
                'message' => 'Delivery is expected soon.',
                'unique_key' => 'goods-expected-soon-'.$record->id,
                'logistics_record_id' => $record->id,
            ])['created_count'];
            $this->auditLogService->write('goods_expected_soon', $record, null, null, null, ['reasons' => $reasons], $record->company_id);
        }

        if (in_array('delivery_date_passed_without_receipt', $reasons, true)) {
            $count += $this->notificationService->notify('date_delay', [
                'company_id' => $record->company_id,
                'title' => 'Logistics delay',
                'message' => 'Delivery date passed without receipt.',
                'unique_key' => 'date-delay-'.$record->id,
                'logistics_record_id' => $record->id,
            ])['created_count'];
        }

        if (in_array('confirmation_exists_ready_date_missing', $reasons, true)) {
            $count += $this->notificationService->notify('missing_ready_date', [
                'company_id' => $record->company_id,
                'title' => 'Missing ready date',
                'message' => 'Supplier confirmation exists but ready date is missing.',
                'unique_key' => 'missing-ready-date-'.$record->id,
                'logistics_record_id' => $record->id,
            ])['created_count'];
        }

        return $count;
    }
}
