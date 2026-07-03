<?php

namespace App\Services\Supply\Calculation;

class OrderNeedCalculator
{
    public const string FORMULA_VERSION = 'v1';

    public function __construct(
        private readonly TrendCalculator $trendCalculator,
        private readonly OrderRoundingService $orderRoundingService,
        private readonly CalculationPeriodService $periodService,
    ) {}

    /**
     * Calculate replenishment need.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function calculate(array $input): array
    {
        $timeline = $this->periodService->validateTimeline($input);
        $warnings = array_values($input['collector_warnings'] ?? []);
        $errors = [];
        $blockingReview = false;

        if (array_intersect($warnings, ['missing_stock_snapshot', 'missing_supplier_product_rule']) !== []) {
            $blockingReview = true;
        }

        if (! $timeline['valid']) {
            $errors = array_merge($errors, $timeline['errors']);
            $blockingReview = true;
        }

        $warnings = array_merge($warnings, $timeline['warnings']);

        $numbers = $this->numbers($input, [
            'last_year_sales_t0_t1',
            'last_year_sales_t1_t2',
            'last_year_sales_t2_t3',
            'free_stock',
            'inbound_until_t1',
            'inbound_t1_t3',
            'reserved_quantity',
        ]);

        if ($numbers['errors'] !== []) {
            $errors = array_merge($errors, $numbers['errors']);
            $blockingReview = true;
        }

        if ($numbers['warnings'] !== []) {
            $warnings = array_merge($warnings, $numbers['warnings']);
            $blockingReview = true;
        }

        if (! isset($input['reservation_strategy'])) {
            $warnings[] = 'reservation_strategy_missing';
            $blockingReview = true;
        }

        $reservationStrategy = $input['reservation_strategy'] ?? null;
        $effectiveReservedQuantity = match ($reservationStrategy) {
            'reserved_not_removed_from_free_stock' => $numbers['values']['reserved_quantity'] ?? 0.0,
            'reserved_already_removed_from_free_stock' => 0.0,
            default => 0.0,
        };

        $trendResult = $this->trendCalculator->calculate($input);
        $warnings = array_merge($warnings, $trendResult['warnings']);
        $errors = array_merge($errors, $trendResult['errors']);

        if ($trendResult['requires_human_review']) {
            $blockingReview = true;
        }

        if ($blockingReview || $trendResult['trend'] === null) {
            return $this->reviewResult($input, $timeline, $trendResult, $numbers['values'], $effectiveReservedQuantity, $warnings, $errors);
        }

        $trend = (float) $trendResult['trend'];
        $needT0T1 = $numbers['values']['last_year_sales_t0_t1'] * $trend;
        $stockT1 = $numbers['values']['free_stock'] + $numbers['values']['inbound_until_t1'] - $needT0T1;
        $needT1T2 = $numbers['values']['last_year_sales_t1_t2'] * $trend;
        $safetyStock = $numbers['values']['last_year_sales_t2_t3'] * $trend;
        $rawNeed = $needT1T2 + $safetyStock - $stockT1 - $numbers['values']['inbound_t1_t3'] + $effectiveReservedQuantity;

        $roundingResult = $this->orderRoundingService->round($rawNeed, [
            'moq' => $input['moq'] ?? null,
            'pack_multiple' => $input['pack_multiple'] ?? null,
            'pallet_quantity' => $input['pallet_quantity'] ?? null,
            'min_transport_quantity' => $input['min_transport_quantity'] ?? null,
            'strategic_minimum_order_enabled' => (bool) ($input['strategic_minimum_order_enabled'] ?? false),
            'pallet_strategy' => $input['pallet_strategy'] ?? null,
            'transport_strategy' => $input['transport_strategy'] ?? null,
            'rounding_strategy' => $input['rounding_strategy'] ?? [],
        ]);

        $warnings = array_merge($warnings, $roundingResult['warnings']);

        if ($roundingResult['status'] === 'needs_review') {
            $blockingReview = true;
        }

        $result = [
            'formula_version' => self::FORMULA_VERSION,
            'status' => $blockingReview ? 'needs_review' : 'ok',
            'trend' => $this->rounded($trend),
            'need_t0_t1' => $this->rounded($needT0T1),
            'stock_t1' => $this->rounded($stockT1),
            'need_t1_t2' => $this->rounded($needT1T2),
            'safety_stock' => $this->rounded($safetyStock),
            'raw_need' => $this->rounded($rawNeed),
            'recommended_quantity' => $this->rounded((float) $roundingResult['quantity']),
            'inbound_until_t1' => $this->rounded($numbers['values']['inbound_until_t1']),
            'inbound_t1_t3' => $this->rounded($numbers['values']['inbound_t1_t3']),
            'reserved_quantity' => $this->rounded($numbers['values']['reserved_quantity']),
            'effective_reserved_quantity' => $this->rounded($effectiveReservedQuantity),
            'applied_rules' => $roundingResult['applied_rules'],
            'warnings' => array_values(array_unique($warnings)),
            'errors' => array_values(array_unique($errors)),
            'requires_human_review' => $blockingReview,
        ];

        $result['explanation'] = $this->explanation($input, $timeline, $numbers['values'], $trendResult, [
            'trend' => $trend,
            'need_t0_t1' => $needT0T1,
            'stock_t1' => $stockT1,
            'need_t1_t2' => $needT1T2,
            'safety_stock' => $safetyStock,
            'raw_need' => $rawNeed,
            'effective_reserved_quantity' => $effectiveReservedQuantity,
            'recommended_quantity' => (float) $roundingResult['quantity'],
        ], $roundingResult, $result['warnings'], $result['errors'], $result['status']);

        return $result;
    }

    /**
     * @param  list<string>  $keys
     * @return array{values:array<string,float>,warnings:list<string>,errors:list<string>}
     */
    private function numbers(array $input, array $keys): array
    {
        $values = [];
        $warnings = [];
        $errors = [];

        foreach ($keys as $key) {
            if (! array_key_exists($key, $input) || $input[$key] === null || $input[$key] === '') {
                $warnings[] = $key.'_missing';
                $values[$key] = 0.0;

                continue;
            }

            if (! is_numeric($input[$key])) {
                $errors[] = $key.'_invalid';
                $values[$key] = 0.0;

                continue;
            }

            $values[$key] = (float) $input[$key];

            if (str_starts_with($key, 'last_year_sales') && $values[$key] < 0) {
                $warnings[] = $key.'_negative';
            }
        }

        return [
            'values' => $values,
            'warnings' => array_values(array_unique($warnings)),
            'errors' => array_values(array_unique($errors)),
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $timeline
     * @param  array<string, mixed>  $trendResult
     * @param  array<string, float>  $numbers
     * @param  list<string>  $warnings
     * @param  list<string>  $errors
     * @return array<string, mixed>
     */
    private function reviewResult(array $input, array $timeline, array $trendResult, array $numbers, float $effectiveReservedQuantity, array $warnings, array $errors): array
    {
        $result = [
            'formula_version' => self::FORMULA_VERSION,
            'status' => 'needs_review',
            'trend' => $trendResult['trend'] === null ? null : $this->rounded((float) $trendResult['trend']),
            'need_t0_t1' => null,
            'stock_t1' => null,
            'need_t1_t2' => null,
            'safety_stock' => null,
            'raw_need' => null,
            'recommended_quantity' => 0.0,
            'inbound_until_t1' => $this->rounded($numbers['inbound_until_t1'] ?? 0.0),
            'inbound_t1_t3' => $this->rounded($numbers['inbound_t1_t3'] ?? 0.0),
            'reserved_quantity' => $this->rounded($numbers['reserved_quantity'] ?? 0.0),
            'effective_reserved_quantity' => $this->rounded($effectiveReservedQuantity),
            'applied_rules' => [],
            'warnings' => array_values(array_unique($warnings)),
            'errors' => array_values(array_unique($errors)),
            'requires_human_review' => true,
        ];

        $result['explanation'] = $this->explanation($input, $timeline, $numbers, $trendResult, [
            'trend' => $trendResult['trend'],
            'need_t0_t1' => null,
            'stock_t1' => null,
            'need_t1_t2' => null,
            'safety_stock' => null,
            'raw_need' => null,
            'effective_reserved_quantity' => $effectiveReservedQuantity,
            'recommended_quantity' => 0.0,
        ], [
            'rounding_steps' => [],
        ], $result['warnings'], $result['errors'], 'needs_review');

        return $result;
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $timeline
     * @param  array<string, float>  $numbers
     * @param  array<string, mixed>  $trendResult
     * @param  array<string, mixed>  $values
     * @param  array<string, mixed>  $roundingResult
     * @param  list<string>  $warnings
     * @param  list<string>  $errors
     * @return array<string, mixed>
     */
    private function explanation(array $input, array $timeline, array $numbers, array $trendResult, array $values, array $roundingResult, array $warnings, array $errors, string $status): array
    {
        return [
            'timeline' => [
                't0_date' => $timeline['dates']['t0_date'] ?? $input['t0_date'] ?? null,
                't1_date' => $timeline['dates']['t1_date'] ?? $input['t1_date'] ?? null,
                't2_date' => $timeline['dates']['t2_date'] ?? $input['t2_date'] ?? null,
                't3_date' => $timeline['dates']['t3_date'] ?? $input['t3_date'] ?? null,
                'periods' => [
                    't0_t1' => 'order execution period',
                    't1_t2' => 'planned coverage period',
                    't2_t3' => 'safety horizon',
                ],
                'note' => 'Safety stock covers only T2-T3 and must not duplicate T1-T2.',
            ],
            'input_values' => [
                'company_id' => $input['company_id'] ?? null,
                'supplier_id' => $input['supplier_id'] ?? null,
                'product_id' => $input['product_id'] ?? null,
                'current_year_sales_for_trend' => $input['current_year_sales_for_trend'] ?? null,
                'last_year_sales_for_trend' => $input['last_year_sales_for_trend'] ?? null,
                'last_year_sales_t0_t1' => $numbers['last_year_sales_t0_t1'] ?? null,
                'last_year_sales_t1_t2' => $numbers['last_year_sales_t1_t2'] ?? null,
                'last_year_sales_t2_t3' => $numbers['last_year_sales_t2_t3'] ?? null,
                'free_stock' => $numbers['free_stock'] ?? null,
                'inbound_until_t1' => $numbers['inbound_until_t1'] ?? null,
                'inbound_t1_t3' => $numbers['inbound_t1_t3'] ?? null,
                'reserved_quantity' => $numbers['reserved_quantity'] ?? null,
                'reservation_strategy' => $input['reservation_strategy'] ?? null,
                'safety_days_rule' => $input['safety_days_rule'] ?? null,
            ],
            'formula_steps' => [
                $this->formulaStep('trend', 'current_year_sales_for_trend / last_year_sales_for_trend', $trendResult['explanation']['calculation'] ?? null, $values['trend']),
                $this->formulaStep('need_t0_t1', 'last_year_sales_t0_t1 * trend', $this->calculation($numbers['last_year_sales_t0_t1'] ?? null, '*', $values['trend'], $values['need_t0_t1']), $values['need_t0_t1']),
                $this->formulaStep('stock_t1', 'free_stock + inbound_until_t1 - need_t0_t1', $this->stockCalculation($numbers, $values), $values['stock_t1']),
                $this->formulaStep('need_t1_t2', 'last_year_sales_t1_t2 * trend', $this->calculation($numbers['last_year_sales_t1_t2'] ?? null, '*', $values['trend'], $values['need_t1_t2']), $values['need_t1_t2']),
                $this->formulaStep('safety_stock', 'last_year_sales_t2_t3 * trend', $this->calculation($numbers['last_year_sales_t2_t3'] ?? null, '*', $values['trend'], $values['safety_stock']), $values['safety_stock']),
                $this->formulaStep('raw_need', 'need_t1_t2 + safety_stock - stock_t1 - inbound_t1_t3 + effective_reserved_quantity', $this->rawNeedCalculation($numbers, $values), $values['raw_need']),
                $this->formulaStep('recommended_quantity', 'raw_need adjusted by MOQ, pack multiple, pallet and transport rules', $this->recommendedCalculation($values, $roundingResult), $values['recommended_quantity']),
            ],
            'rounding_steps' => $roundingResult['rounding_steps'] ?? [],
            'warnings' => $warnings,
            'errors' => $errors,
            'final_result' => [
                'status' => $status,
                'raw_need' => $values['raw_need'],
                'recommended_quantity' => $values['recommended_quantity'],
                'requires_human_review' => $status === 'needs_review',
            ],
        ];
    }

    /**
     * @return array{name:string,formula:string,calculation:string|null,value:mixed}
     */
    private function formulaStep(string $name, string $formula, ?string $calculation, mixed $value): array
    {
        return [
            'name' => $name,
            'formula' => $formula,
            'calculation' => $calculation,
            'value' => is_float($value) ? $this->rounded($value) : $value,
        ];
    }

    private function calculation(mixed $left, string $operator, mixed $right, mixed $result): ?string
    {
        if ($left === null || $right === null || $result === null) {
            return null;
        }

        return $this->formatNumber((float) $left).' '.$operator.' '.$this->formatNumber((float) $right).' = '.$this->formatNumber((float) $result);
    }

    /**
     * @param  array<string, float>  $numbers
     * @param  array<string, mixed>  $values
     */
    private function stockCalculation(array $numbers, array $values): ?string
    {
        if ($values['need_t0_t1'] === null || $values['stock_t1'] === null) {
            return null;
        }

        return $this->formatNumber($numbers['free_stock'] ?? 0.0)
            .' + '.$this->formatNumber($numbers['inbound_until_t1'] ?? 0.0)
            .' - '.$this->formatNumber((float) $values['need_t0_t1'])
            .' = '.$this->formatNumber((float) $values['stock_t1']);
    }

    /**
     * @param  array<string, float>  $numbers
     * @param  array<string, mixed>  $values
     */
    private function rawNeedCalculation(array $numbers, array $values): ?string
    {
        if ($values['raw_need'] === null) {
            return null;
        }

        return $this->formatNumber((float) $values['need_t1_t2'])
            .' + '.$this->formatNumber((float) $values['safety_stock'])
            .' - '.$this->formatNumber((float) $values['stock_t1'])
            .' - '.$this->formatNumber($numbers['inbound_t1_t3'] ?? 0.0)
            .' + '.$this->formatNumber((float) $values['effective_reserved_quantity'])
            .' = '.$this->formatNumber((float) $values['raw_need']);
    }

    /**
     * @param  array<string, mixed>  $values
     * @param  array<string, mixed>  $roundingResult
     */
    private function recommendedCalculation(array $values, array $roundingResult): ?string
    {
        $steps = $roundingResult['rounding_steps'] ?? [];

        foreach (array_reverse($steps) as $step) {
            if (($step['name'] ?? null) === 'pack_multiple') {
                return $step['calculation'];
            }
        }

        if ($values['raw_need'] === null || $values['recommended_quantity'] === null) {
            return null;
        }

        return $this->formatNumber((float) $values['recommended_quantity']);
    }

    private function rounded(float $value): float
    {
        return round($value, 6);
    }

    private function formatNumber(float $value): string
    {
        return rtrim(rtrim(number_format($value, 6, '.', ''), '0'), '.');
    }
}
