<?php

namespace App\Services\Supply\Analytics;

use App\Models\LogisticsRecord;
use App\Models\User;

class LogisticsPerformanceReportService
{
    public function __construct(private readonly AnalyticsFilterService $filters) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function report(array $filters = [], ?User $user = null): array
    {
        $normalized = $this->filters->normalize($filters);
        $createdFrom = $normalized['date_from'].' 00:00:00';
        $createdTo = $normalized['date_to'].' 23:59:59';
        $records = LogisticsRecord::query()
            ->select(['id', 'company_id', 'supplier_id', 'carrier_id', 'supplier_order_id', 'order_date', 'confirmation_date', 'ready_date', 'pickup_date', 'delivery_date', 'actual_received_date', 'status'])
            ->when($normalized['company_id'], fn ($query, int $companyId) => $query->where('company_id', $companyId))
            ->when($normalized['supplier_id'], fn ($query, int $supplierId) => $query->where('supplier_id', $supplierId))
            ->when($normalized['carrier_id'], fn ($query, int $carrierId) => $query->where('carrier_id', $carrierId))
            ->whereBetween('created_at', [$createdFrom, $createdTo])
            ->with(['supplier:id,name', 'carrier:id,name'])
            ->latest('id')
            ->limit(500)
            ->get();

        $delayed = $records->filter(fn (LogisticsRecord $record): bool => $this->status($record->status) === 'delayed');
        $completed = $records->filter(fn (LogisticsRecord $record): bool => $this->status($record->status) === 'completed');
        $received = $records->filter(fn (LogisticsRecord $record): bool => $record->actual_received_date !== null);
        $onTime = $received->filter(fn (LogisticsRecord $record): bool => $record->delivery_date !== null && $record->actual_received_date->lessThanOrEqualTo($record->delivery_date));

        $rows = $records->map(fn (LogisticsRecord $record): array => [
            'logistics_record_id' => $record->id,
            'supplier' => $record->supplier?->name,
            'carrier' => $record->carrier?->name,
            'status' => $this->status($record->status),
            'order_date' => $record->order_date?->toDateString(),
            'confirmation_date' => $record->confirmation_date?->toDateString(),
            'ready_date' => $record->ready_date?->toDateString(),
            'pickup_date' => $record->pickup_date?->toDateString(),
            'delivery_date' => $record->delivery_date?->toDateString(),
            'actual_received_date' => $record->actual_received_date?->toDateString(),
        ])->values()->all();

        return [
            'type' => 'logistics_performance',
            'title' => 'Logistics Performance',
            'description' => 'Logistics delays, stage durations and missing date quality.',
            'filters' => $normalized,
            'summary' => [
                'open_logistics_records' => $records->reject(fn (LogisticsRecord $record): bool => in_array($this->status($record->status), ['completed', 'cancelled'], true))->count(),
                'delayed_records' => $delayed->count(),
                'completed_records' => $completed->count(),
                'delay_rate' => $this->percentage($delayed->count(), $records->count()),
                'on_time_delivery_rate' => $this->percentage($onTime->count(), $received->count()),
                'expected_soon_count' => $records->filter(fn (LogisticsRecord $record): bool => $record->delivery_date !== null && $record->delivery_date->between(now(), now()->addDays(7)))->count(),
                'missing_ready_date_count' => $records->whereNull('ready_date')->count(),
                'missing_carrier_count' => $records->whereNull('carrier_id')->count(),
                'logistics_needs_review_count' => $records->filter(fn (LogisticsRecord $record): bool => $this->status($record->status) === 'needs_review')->count(),
                'average_days_order_to_confirmation' => $this->averageDays($records, 'order_date', 'confirmation_date'),
                'average_days_confirmation_to_ready' => $this->averageDays($records, 'confirmation_date', 'ready_date'),
                'average_days_ready_to_pickup' => $this->averageDays($records, 'ready_date', 'pickup_date'),
                'average_days_pickup_to_delivery' => $this->averageDays($records, 'pickup_date', 'delivery_date'),
                'average_days_delivery_to_receipt' => $this->averageDays($records, 'delivery_date', 'actual_received_date'),
            ],
            'rows' => $rows,
            'warnings' => array_merge($normalized['warnings'], $records->isEmpty() ? ['No logistics records found for the selected period.'] : []),
            'definitions' => app(KpiDefinitionService::class)->definitions(),
        ];
    }

    private function percentage(int|float $value, int|float $total): float
    {
        if ($total <= 0) {
            return 0.0;
        }

        return round(($value / $total) * 100, 2);
    }

    private function averageDays($records, string $from, string $to): float
    {
        $days = $records
            ->filter(fn (LogisticsRecord $record): bool => $record->{$from} !== null && $record->{$to} !== null)
            ->map(fn (LogisticsRecord $record): int => $record->{$from}->diffInDays($record->{$to}));

        return round((float) $days->avg(), 2);
    }

    private function status(mixed $status): string
    {
        return $status instanceof \BackedEnum ? $status->value : (string) $status;
    }
}
