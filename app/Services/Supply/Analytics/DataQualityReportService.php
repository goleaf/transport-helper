<?php

namespace App\Services\Supply\Analytics;

use App\Models\Carrier;
use App\Models\ImportRow;
use App\Models\IntegrationConnection;
use App\Models\Product;
use App\Models\SalesHistory;
use App\Models\StockSnapshot;
use App\Models\Supplier;
use App\Models\User;

class DataQualityReportService
{
    public function __construct(private readonly AnalyticsFilterService $filters) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function report(array $filters = [], ?User $user = null): array
    {
        $normalized = $this->filters->normalize($filters);
        $issues = [
            $this->issue('products_without_supplier_rules', 'critical', Product::query()->doesntHave('supplierProductRules')->count(), 'Products without supplier product rules.'),
            $this->issue('supplier_rules_missing_pack_multiple', 'warning', Product::query()->whereHas('supplierProductRules', fn ($query) => $query->whereNull('pack_multiple'))->count(), 'Supplier rules missing pack multiple.'),
            $this->issue('stock_snapshots_missing', 'critical', Product::query()->doesntHave('stockSnapshots')->count(), 'Products without stock snapshots.'),
            $this->issue('sales_history_missing', 'warning', Product::query()->doesntHave('salesHistory')->count(), 'Products without sales history.'),
            $this->issue('import_rows_failed', 'critical', ImportRow::query()->where('status', 'failed')->count(), 'Failed import rows need review.'),
            $this->issue('suppliers_without_order_contacts', 'critical', Supplier::query()->whereDoesntHave('contacts', fn ($query) => $query->where('receives_orders', true)->where('is_active', true))->count(), 'Suppliers without active order contacts.'),
            $this->issue('carriers_without_contacts', 'warning', Carrier::query()->whereDoesntHave('contacts', fn ($query) => $query->where('is_active', true))->count(), 'Carriers without active contacts.'),
            $this->issue('integrations_active_not_tested', 'warning', IntegrationConnection::query()->where('is_active', true)->where('last_test_status', '!=', 'success')->count(), 'Active integrations without successful latest test.'),
            $this->issue('stale_stock_snapshots', 'warning', StockSnapshot::query()->where('snapshot_date', '<', now()->subDays(7)->toDateString())->count(), 'Stock snapshots older than seven days.'),
            $this->issue('recent_sales_history_missing', 'info', SalesHistory::query()->where('sales_date', '>=', now()->subDays(30)->toDateString())->count() === 0 ? 1 : 0, 'No recent sales history in the last 30 days.'),
        ];

        return [
            'type' => 'data_quality',
            'title' => 'Data Quality',
            'description' => 'Data completeness and import quality warnings for reliable analytics.',
            'filters' => $normalized,
            'summary' => [
                'critical' => collect($issues)->where('severity', 'critical')->sum('count'),
                'warning' => collect($issues)->where('severity', 'warning')->sum('count'),
                'info' => collect($issues)->where('severity', 'info')->sum('count'),
            ],
            'issues' => $issues,
            'rows' => $issues,
            'warnings' => $normalized['warnings'],
            'definitions' => app(KpiDefinitionService::class)->definitions(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function issue(string $key, string $severity, int $count, string $message): array
    {
        return [
            'key' => $key,
            'severity' => $severity,
            'count' => $count,
            'message' => $message,
            'recommended_action' => $count > 0 ? 'Review and correct source data.' : 'No action needed.',
        ];
    }
}
