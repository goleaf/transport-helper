<?php

namespace App\Services\Supply\Analytics;

use App\Models\OrderProposalItem;
use App\Models\User;

class ForecastAccuracyReportService
{
    public function __construct(private readonly AnalyticsFilterService $filters) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function report(array $filters = [], ?User $user = null): array
    {
        $normalized = $this->filters->normalize($filters);
        $items = OrderProposalItem::query()
            ->select(['id', 'order_proposal_id', 'product_id', 't1_date', 't2_date', 'recommended_quantity', 'approved_quantity', 'user_adjusted_quantity'])
            ->when($normalized['product_id'], fn ($query, int $productId) => $query->where('product_id', $productId))
            ->with([
                'product:id,sku,name',
                'product.salesHistory' => fn ($query) => $query->select(['id', 'product_id', 'sales_date', 'quantity'])->whereBetween('sales_date', [$normalized['date_from'], $normalized['date_to']]),
            ])
            ->latest('id')
            ->limit(500)
            ->get();

        $rows = $items->map(function (OrderProposalItem $item): array {
            $actualSales = (float) $item->product?->salesHistory?->sum('quantity');
            $approved = (float) ($item->approved_quantity ?? $item->user_adjusted_quantity ?? $item->recommended_quantity);
            $absoluteError = abs($approved - $actualSales);
            $percentageError = $actualSales > 0 ? round(($absoluteError / $actualSales) * 100, 2) : null;

            return [
                'proposal_item_id' => $item->id,
                'sku' => $item->product?->sku,
                'product' => $item->product?->name,
                'forecast_quantity' => (float) $item->recommended_quantity,
                'recommended_quantity' => (float) $item->recommended_quantity,
                'approved_quantity' => $approved,
                'actual_sales_in_coverage_period' => $actualSales,
                'absolute_error' => $absoluteError,
                'percentage_error' => $percentageError,
                'bias' => $this->bias($approved, $actualSales),
            ];
        })->values()->all();

        $warnings = $normalized['warnings'];
        if ($rows === []) {
            $warnings[] = 'Forecast accuracy is unavailable without proposal items and later actual sales.';
        } elseif (collect($rows)->where('actual_sales_in_coverage_period', 0.0)->isNotEmpty()) {
            $warnings[] = 'Some forecast rows have insufficient actual sales.';
        }

        return [
            'type' => 'forecast_accuracy',
            'title' => 'Forecast Accuracy',
            'description' => 'Compares recommended and approved quantities to later actual sales.',
            'filters' => $normalized,
            'summary' => [
                'rows' => count($rows),
                'average_absolute_error' => round((float) collect($rows)->avg('absolute_error'), 2),
                'average_percentage_error' => round((float) collect($rows)->whereNotNull('percentage_error')->avg('percentage_error'), 2),
            ],
            'rows' => $rows,
            'warnings' => $warnings,
            'definitions' => app(KpiDefinitionService::class)->definitions(),
        ];
    }

    private function bias(float $approved, float $actualSales): string
    {
        if ($actualSales <= 0) {
            return 'insufficient_actual_sales';
        }

        if ($approved > $actualSales) {
            return 'over_ordered';
        }

        if ($approved < $actualSales) {
            return 'under_ordered';
        }

        return 'accurate';
    }
}
