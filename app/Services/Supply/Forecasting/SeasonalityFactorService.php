<?php

namespace App\Services\Supply\Forecasting;

use App\Models\Company;
use App\Models\Product;
use App\Models\SalesHistory;
use Carbon\CarbonImmutable;

class SeasonalityFactorService
{
    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function calculateFactor(Company $company, Product $product, string $targetStart, string $targetEnd, array $options = []): array
    {
        $rows = SalesHistory::query()
            ->select(['id', 'company_id', 'product_id', 'sales_date', 'quantity'])
            ->where('company_id', $company->getKey())
            ->where('product_id', $product->getKey())
            ->whereDate('sales_date', '<', $targetStart)
            ->orderBy('sales_date')
            ->get();

        return $this->factorFromRows($rows, $targetStart, $targetEnd, $options + [
            'scope' => 'product',
            'scope_id' => $product->getKey(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function calculateCategoryFactor(Company $company, string $category, string $targetStart, string $targetEnd, array $options = []): array
    {
        $rows = SalesHistory::query()
            ->select(['id', 'company_id', 'product_id', 'sales_date', 'quantity'])
            ->where('company_id', $company->getKey())
            ->whereDate('sales_date', '<', $targetStart)
            ->whereHas('product', fn ($query) => $query->where('category', $category))
            ->orderBy('sales_date')
            ->get();

        return $this->factorFromRows($rows, $targetStart, $targetEnd, $options + [
            'scope' => 'category',
            'scope_id' => $category,
        ]);
    }

    /**
     * @param  iterable<int, SalesHistory>  $rows
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function factorFromRows(iterable $rows, string $targetStart, string $targetEnd, array $options): array
    {
        $targetMonth = CarbonImmutable::parse($targetStart)->format('m');
        $monthlyTotals = collect($rows)
            ->groupBy(fn (SalesHistory $row): string => $row->sales_date->format('Y-m'))
            ->map(fn ($monthRows): float => round($monthRows->sum(fn (SalesHistory $row): float => (float) $row->quantity), 4));

        $minimumMonths = (int) ($options['minimum_history_months'] ?? config('supply.forecasting.seasonality.minimum_history_months', 12));

        if ($monthlyTotals->count() < $minimumMonths) {
            return [
                'factor' => 1.0,
                'status' => 'warning',
                'warnings' => ['insufficient_history'],
                'explanation' => [
                    'method' => 'same_month_vs_average_month',
                    'target_start' => $targetStart,
                    'target_end' => $targetEnd,
                    'history_months' => $monthlyTotals->count(),
                    'minimum_history_months' => $minimumMonths,
                    'calculation' => 'insufficient history; factor 1.0 used',
                ],
            ];
        }

        $sameMonthTotals = $monthlyTotals
            ->filter(fn (float $quantity, string $yearMonth): bool => str_ends_with($yearMonth, '-'.$targetMonth))
            ->values();

        $samePeriodAverage = $sameMonthTotals->count() > 0 ? (float) $sameMonthTotals->average() : 0.0;
        $baselineAverage = $monthlyTotals->count() > 0 ? (float) $monthlyTotals->average() : 0.0;

        if ($samePeriodAverage <= 0 || $baselineAverage <= 0) {
            return [
                'factor' => 1.0,
                'status' => 'warning',
                'warnings' => ['seasonality_baseline_unavailable'],
                'explanation' => [
                    'method' => 'same_month_vs_average_month',
                    'same_period_average' => $samePeriodAverage,
                    'baseline_average' => $baselineAverage,
                    'calculation' => 'baseline unavailable; factor 1.0 used',
                ],
            ];
        }

        $rawFactor = $samePeriodAverage / $baselineAverage;
        $min = (float) ($options['min_factor'] ?? config('supply.forecasting.seasonality.min_factor', 0.50));
        $max = (float) ($options['max_factor'] ?? config('supply.forecasting.seasonality.max_factor', 2.00));
        $factor = min(max($rawFactor, $min), $max);
        $warnings = [];

        if ($factor !== $rawFactor) {
            $warnings[] = 'seasonality_factor_clamped';
        }

        return [
            'factor' => round($factor, 6),
            'status' => $warnings === [] ? 'ok' : 'warning',
            'warnings' => $warnings,
            'explanation' => [
                'method' => 'same_month_vs_average_month',
                'scope' => $options['scope'] ?? 'product',
                'scope_id' => $options['scope_id'] ?? null,
                'same_period_average' => round($samePeriodAverage, 4),
                'baseline_average' => round($baselineAverage, 4),
                'raw_factor' => round($rawFactor, 6),
                'min_factor' => $min,
                'max_factor' => $max,
                'calculation' => round($samePeriodAverage, 4).' / '.round($baselineAverage, 4).' = '.round($rawFactor, 6),
            ],
        ];
    }
}
