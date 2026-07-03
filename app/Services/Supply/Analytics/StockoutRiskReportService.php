<?php

namespace App\Services\Supply\Analytics;

use App\Models\Product;
use App\Models\User;

class StockoutRiskReportService
{
    public function __construct(private readonly AnalyticsFilterService $filters) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function report(array $filters = [], ?User $user = null): array
    {
        $normalized = $this->filters->normalize($filters);
        $products = Product::query()
            ->select(['id', 'company_id', 'sku', 'name', 'category'])
            ->when($normalized['company_id'], fn ($query, int $companyId) => $query->where('company_id', $companyId))
            ->when($normalized['product_id'], fn ($query, int $productId) => $query->whereKey($productId))
            ->when($normalized['category'], fn ($query, string $category) => $query->where('category', $category))
            ->with([
                'stockSnapshots' => fn ($query) => $query->select(['id', 'product_id', 'snapshot_date', 'free_stock', 'in_transit_quantity', 'reserved_quantity'])->latest('snapshot_date')->limit(1),
                'salesHistory' => fn ($query) => $query->select(['id', 'product_id', 'sales_date', 'quantity'])->whereBetween('sales_date', [$normalized['date_from'], $normalized['date_to']]),
                'supplierProductRules' => fn ($query) => $query->select(['id', 'supplier_id', 'product_id', 'lead_time_days', 'safety_days', 'pack_multiple', 'moq'])->with(['supplier:id,name']),
                'inboundOrderItems' => fn ($query) => $query->select(['id', 'product_id', 'expected_arrival_date', 'ordered_quantity', 'received_quantity', 'status']),
            ])
            ->orderBy('sku')
            ->limit(500)
            ->get();

        $rows = $products->map(function (Product $product) use ($normalized): array {
            $snapshot = $product->stockSnapshots->first();
            $totalSales = (float) $product->salesHistory->sum('quantity');
            $days = max(1, now()->parse($normalized['date_from'])->diffInDays(now()->parse($normalized['date_to'])) + 1);
            $velocity = $totalSales / $days;
            $leadTime = (int) ($product->supplierProductRules->first()?->lead_time_days ?? 0);
            $freeStock = $snapshot ? (float) $snapshot->free_stock : null;
            $daysLeft = $freeStock !== null && $velocity > 0 ? round($freeStock / $velocity, 2) : null;
            $risk = $this->riskLevel($snapshot !== null, $product->salesHistory->isNotEmpty(), $freeStock, $velocity, $daysLeft, $leadTime);
            $inbound = $product->inboundOrderItems->sortBy('expected_arrival_date')->first();

            return [
                'product_id' => $product->id,
                'sku' => $product->sku,
                'product' => $product->name,
                'free_stock' => $freeStock,
                'average_daily_sales' => round($velocity, 3),
                'days_of_stock_left' => $daysLeft,
                'next_inbound_date' => $inbound?->expected_arrival_date?->toDateString(),
                'next_inbound_quantity' => $inbound ? max(0, (float) $inbound->ordered_quantity - (float) $inbound->received_quantity) : 0,
                'delayed_inbound' => false,
                'reservations' => $snapshot ? (float) $snapshot->reserved_quantity : null,
                'lead_time_days' => $leadTime,
                'risk_level' => $risk,
                'recommended_action' => $this->action($risk),
            ];
        })->values()->all();

        $warnings = $normalized['warnings'];
        if ($rows === []) {
            $warnings[] = 'No products found for stockout risk reporting.';
        }

        if (collect($rows)->every(fn (array $row): bool => $row['free_stock'] === null)) {
            $warnings[] = 'No stock snapshots found for the selected period.';
        }

        return [
            'type' => 'stockout_risk',
            'title' => 'Stockout Risk',
            'description' => 'Read-only stockout risk based on current stock, sales velocity and lead time.',
            'filters' => $normalized,
            'summary' => [
                'critical_count' => collect($rows)->where('risk_level', 'critical')->count(),
                'high_count' => collect($rows)->where('risk_level', 'high')->count(),
                'unknown_data_count' => collect($rows)->where('risk_level', 'unknown_data')->count(),
            ],
            'rows' => $rows,
            'warnings' => $warnings,
            'definitions' => app(KpiDefinitionService::class)->definitions(),
        ];
    }

    private function riskLevel(bool $hasStock, bool $hasSales, ?float $freeStock, float $velocity, ?float $daysLeft, int $leadTime): string
    {
        if (! $hasStock || ! $hasSales) {
            return 'unknown_data';
        }

        if ($freeStock !== null && $freeStock <= 0 && $velocity > 0) {
            return 'critical';
        }

        if ($daysLeft !== null && $leadTime > 0 && $daysLeft < $leadTime) {
            return 'high';
        }

        if ($daysLeft !== null && $leadTime > 0 && $daysLeft < ($leadTime + 7)) {
            return 'medium';
        }

        return 'low';
    }

    private function action(string $risk): string
    {
        return match ($risk) {
            'critical' => 'Review replenishment immediately.',
            'high' => 'Check inbound dates and supplier lead time.',
            'medium' => 'Monitor next calculation run.',
            'unknown_data' => 'Import stock and sales data before relying on risk score.',
            default => 'No immediate action.',
        };
    }
}
