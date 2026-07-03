<?php

namespace App\Services\Supply\Forecasting;

use App\Models\Company;
use App\Models\Product;
use App\Models\SalesExclusionRule;
use App\Models\SalesHistory;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class SalesSeriesService
{
    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function salesSum(Company $company, Product $product, string $dateFrom, string $dateTo, array $options = []): array
    {
        $rows = $this->salesRows($company, [$product->getKey()], $dateFrom, $dateTo, $options);
        $rules = $this->rules($options);
        $outlierIds = $this->outlierIds($rows, $options);
        $included = [];
        $excludedReasons = [];
        $warnings = [];

        foreach ($rows as $row) {
            $reason = $this->exclusionReason($row, $product, $rules, $outlierIds, $options);

            if ($reason !== null) {
                $excludedReasons[$reason] = ($excludedReasons[$reason] ?? 0) + 1;

                continue;
            }

            $included[] = $row;
        }

        if ($outlierIds !== [] && ! (bool) ($options['exclude_outlier_candidates'] ?? false)) {
            $warnings[] = 'outlier_candidates_detected_not_excluded';
        }

        return [
            'sum' => round(collect($included)->sum(fn (SalesHistory $row): float => (float) $row->quantity), 4),
            'included_rows_count' => count($included),
            'excluded_rows_count' => array_sum($excludedReasons),
            'excluded_reasons' => $excludedReasons,
            'warnings' => $warnings,
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return list<array{date:string,quantity:float,included_rows_count:int,excluded_rows_count:int,excluded_reasons:array<string,int>}>
     */
    public function dailySeries(Company $company, Product $product, string $dateFrom, string $dateTo, array $options = []): array
    {
        $period = CarbonPeriod::create($dateFrom, $dateTo);

        return collect($period)
            ->map(function ($date) use ($company, $product, $options): array {
                $dateString = CarbonImmutable::parse($date)->toDateString();
                $sum = $this->salesSum($company, $product, $dateString, $dateString, $options);

                return [
                    'date' => $dateString,
                    'quantity' => (float) $sum['sum'],
                    'included_rows_count' => (int) $sum['included_rows_count'],
                    'excluded_rows_count' => (int) $sum['excluded_rows_count'],
                    'excluded_reasons' => $sum['excluded_reasons'],
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  list<int>  $productIds
     * @param  array<string, mixed>  $options
     * @return array<int, array<string, mixed>>
     */
    public function periodSalesByProducts(Company $company, array $productIds, string $dateFrom, string $dateTo, array $options = []): array
    {
        $products = Product::query()
            ->select(['id', 'company_id', 'sku', 'name', 'category'])
            ->where('company_id', $company->getKey())
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        $rowsByProduct = $this->salesRows($company, $productIds, $dateFrom, $dateTo, $options)->groupBy('product_id');
        $rules = $this->rules($options);

        return $products
            ->mapWithKeys(function (Product $product) use ($rowsByProduct, $rules, $options): array {
                $rows = $rowsByProduct->get($product->getKey(), collect());
                $outlierIds = $this->outlierIds($rows, $options);
                $included = [];
                $excludedReasons = [];
                $warnings = [];

                foreach ($rows as $row) {
                    $reason = $this->exclusionReason($row, $product, $rules, $outlierIds, $options);

                    if ($reason !== null) {
                        $excludedReasons[$reason] = ($excludedReasons[$reason] ?? 0) + 1;

                        continue;
                    }

                    $included[] = $row;
                }

                if ($outlierIds !== [] && ! (bool) ($options['exclude_outlier_candidates'] ?? false)) {
                    $warnings[] = 'outlier_candidates_detected_not_excluded';
                }

                return [
                    $product->getKey() => [
                        'sum' => round(collect($included)->sum(fn (SalesHistory $row): float => (float) $row->quantity), 4),
                        'included_rows_count' => count($included),
                        'excluded_rows_count' => array_sum($excludedReasons),
                        'excluded_reasons' => $excludedReasons,
                        'warnings' => $warnings,
                    ],
                ];
            })
            ->all();
    }

    /**
     * @param  list<int>  $productIds
     * @param  array<string, mixed>  $options
     * @return EloquentCollection<int, SalesHistory>
     */
    private function salesRows(Company $company, array $productIds, string $dateFrom, string $dateTo, array $options): EloquentCollection
    {
        $query = SalesHistory::query()
            ->select([
                'id',
                'company_id',
                'product_id',
                'sales_date',
                'quantity',
                'channel',
                'is_promotion',
                'is_anomaly',
                'anomaly_reason',
            ])
            ->where('company_id', $company->getKey())
            ->whereIn('product_id', $productIds)
            ->whereDate('sales_date', '>=', $dateFrom)
            ->whereDate('sales_date', '<=', $dateTo)
            ->orderBy('sales_date')
            ->orderBy('id');

        $includeChannels = collect($options['include_channels'] ?? [])->filter()->values()->all();
        if ($includeChannels !== []) {
            $query->whereIn('channel', $includeChannels);
        }

        $excludeChannels = collect($options['exclude_channels'] ?? [])->filter()->values()->all();
        if ($excludeChannels !== []) {
            $query->whereNotIn('channel', $excludeChannels);
        }

        return $query->get();
    }

    /**
     * @param  array<string, mixed>  $options
     * @return EloquentCollection<int, SalesExclusionRule>
     */
    private function rules(array $options): EloquentCollection
    {
        $ruleIds = collect($options['exclusion_rule_ids'] ?? [])->filter()->values()->all();

        if ($ruleIds === []) {
            return new EloquentCollection;
        }

        return SalesExclusionRule::query()
            ->select(['id', 'company_id', 'supplier_id', 'product_id', 'category', 'rule_type', 'date_from', 'date_to', 'applies_to', 'reason', 'is_active'])
            ->whereIn('id', $ruleIds)
            ->where('is_active', true)
            ->get();
    }

    /**
     * @param  iterable<int, SalesHistory>  $rows
     * @param  array<string, mixed>  $options
     * @return list<int>
     */
    private function outlierIds(iterable $rows, array $options): array
    {
        if (! (bool) ($options['outlier_detection'] ?? false)) {
            return [];
        }

        $quantities = collect($rows)
            ->map(fn (SalesHistory $row): float => (float) $row->quantity)
            ->sort()
            ->values();

        if ($quantities->count() < 3) {
            return [];
        }

        $middle = (int) floor($quantities->count() / 2);
        $median = $quantities->count() % 2 === 0
            ? (($quantities[$middle - 1] + $quantities[$middle]) / 2)
            : $quantities[$middle];

        if ($median <= 0) {
            return [];
        }

        $multiplier = (float) ($options['outlier_multiplier'] ?? config('supply.forecasting.outliers.default_multiplier', 3.0));
        $threshold = $median * $multiplier;

        return collect($rows)
            ->filter(fn (SalesHistory $row): bool => (float) $row->quantity > $threshold)
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->values()
            ->all();
    }

    /**
     * @param  EloquentCollection<int, SalesExclusionRule>  $rules
     * @param  list<int>  $outlierIds
     * @param  array<string, mixed>  $options
     */
    private function exclusionReason(SalesHistory $row, Product $product, EloquentCollection $rules, array $outlierIds, array $options): ?string
    {
        if ((bool) ($options['exclude_promotions'] ?? false) && $row->is_promotion) {
            return 'promotion';
        }

        if ((bool) ($options['exclude_anomalies'] ?? false) && $row->is_anomaly) {
            return 'anomaly';
        }

        foreach ($rules as $rule) {
            if ($this->ruleMatchesRow($rule, $row, $product, $options)) {
                $type = $rule->rule_type instanceof \BackedEnum ? $rule->rule_type->value : (string) $rule->rule_type;

                return $type ?: 'manual_exclusion';
            }
        }

        if (in_array((int) $row->id, $outlierIds, true) && (bool) ($options['exclude_outlier_candidates'] ?? false)) {
            return 'outlier';
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function ruleMatchesRow(SalesExclusionRule $rule, SalesHistory $row, Product $product, array $options): bool
    {
        $date = $row->sales_date?->toDateString();

        if ($date === null || $date < $rule->date_from->toDateString() || $date > $rule->date_to->toDateString()) {
            return false;
        }

        if ($rule->product_id !== null && (int) $rule->product_id !== (int) $product->getKey()) {
            return false;
        }

        if ($rule->category !== null && $rule->category !== $product->category) {
            return false;
        }

        if ($rule->supplier_id !== null && (int) $rule->supplier_id !== (int) ($options['supplier_id'] ?? 0)) {
            return false;
        }

        return true;
    }
}
