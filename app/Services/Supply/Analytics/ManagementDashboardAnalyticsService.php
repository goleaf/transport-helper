<?php

namespace App\Services\Supply\Analytics;

use App\Models\AiEmailExtraction;
use App\Models\CarrierQuote;
use App\Models\FormAutofillRun;
use App\Models\ImportBatch;
use App\Models\LogisticsRecord;
use App\Models\SupplierOrder;
use App\Models\User;

class ManagementDashboardAnalyticsService
{
    public function __construct(
        private readonly AnalyticsFilterService $filters,
        private readonly StockoutRiskReportService $stockoutRiskReport,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function dashboard(array $filters = [], ?User $user = null): array
    {
        $normalized = $this->filters->normalize($filters);
        $stockout = $this->stockoutRiskReport->report($filters, $user);
        $supplierOrders = SupplierOrder::query()->select(['id', 'supplier_id', 'status'])->with(['supplier:id,name'])->limit(500)->get();
        $logistics = LogisticsRecord::query()->select(['id', 'supplier_id', 'status', 'delivery_date', 'actual_received_date'])->with(['supplier:id,name'])->limit(500)->get();
        $imports = ImportBatch::query()->select(['id', 'total_rows', 'failed_rows'])->limit(500)->get();

        $needsReviewTotal = $supplierOrders->filter(fn (SupplierOrder $order): bool => $this->status($order->status) === 'needs_review')->count()
            + $logistics->filter(fn (LogisticsRecord $record): bool => $this->status($record->status) === 'needs_review')->count()
            + AiEmailExtraction::query()->where('requires_human_review', true)->count()
            + FormAutofillRun::query()->where('status', 'needs_review')->count();

        return [
            'type' => 'management_dashboard',
            'title' => 'Management Analytics',
            'filters' => $normalized,
            'summary' => [
                'open_supplier_orders' => $supplierOrders->filter(fn (SupplierOrder $order): bool => ! in_array($this->status($order->status), ['completed', 'cancelled'], true))->count(),
                'delayed_logistics' => $logistics->filter(fn (LogisticsRecord $record): bool => $this->status($record->status) === 'delayed')->count(),
                'needs_review_total' => $needsReviewTotal,
                'emails_waiting_review' => SupplierOrder::query()->where('status', 'email_prepared')->count(),
                'carrier_quotes_waiting_selection' => CarrierQuote::query()->whereIn('status', ['received', 'needs_review'])->count(),
                'goods_expected_soon' => $logistics->filter(fn (LogisticsRecord $record): bool => $record->delivery_date !== null && $record->actual_received_date === null && $record->delivery_date->between(now(), now()->addDays(7)))->count(),
                'stockout_risk_skus' => $stockout['summary']['critical_count'] + $stockout['summary']['high_count'],
                'import_error_rate' => $this->percentage((int) $imports->sum('failed_rows'), (int) $imports->sum('total_rows')),
            ],
            'trends' => [
                'supplier_order_count_by_week' => [],
                'delays_by_week' => [],
            ],
            'top_risks' => collect($stockout['rows'])->whereIn('risk_level', ['critical', 'high'])->take(10)->values()->all(),
            'supplier_summary' => $supplierOrders
                ->groupBy(fn (SupplierOrder $order): string => $order->supplier?->name ?? 'Unknown supplier')
                ->map(fn ($orders, string $supplier): array => ['supplier' => $supplier, 'open_orders' => $orders->count()])
                ->values()
                ->all(),
            'warnings' => array_merge($normalized['warnings'], $stockout['warnings']),
            'definitions' => app(KpiDefinitionService::class)->definitions(),
        ];
    }

    private function percentage(int|float $value, int|float $total): float
    {
        return $total > 0 ? round(($value / $total) * 100, 2) : 0.0;
    }

    private function status(mixed $status): string
    {
        return $status instanceof \BackedEnum ? $status->value : (string) $status;
    }
}
