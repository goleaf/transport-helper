<?php

namespace App\Services\Supply;

class OrderNeedCalculator
{
    public const string FORMULA_VERSION = 'order_need_t0_t1_t2_t3_v1';

    public function __construct(
        private TrendCalculator $trendCalculator,
        private OrderRoundingService $orderRoundingService,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function calculate(array $input): array
    {
        $trendResult = $this->trendCalculator->calculate($input);
        $warnings = $trendResult['warnings'];
        $requiresHumanReview = (bool) $trendResult['requires_human_review'];

        if ($trendResult['trend'] === null) {
            return $this->reviewResult($input, $trendResult, $warnings);
        }

        $trend = (float) $trendResult['trend'];
        $needT0T1 = $this->number($input, 'last_year_sales_t0_t1') * $trend;
        $stockT1 = $this->number($input, 'free_stock') + $this->number($input, 'inbound_until_t1') - $needT0T1;
        $needT1T2 = $this->number($input, 'last_year_sales_t1_t2') * $trend;
        $safetyStock = $this->number($input, 'last_year_sales_t2_t3') * $trend;
        $reservedQuantity = $this->reservationQuantity($input);
        $rawNeed = $needT1T2 + $safetyStock - $stockT1 - $this->number($input, 'inbound_t1_t3') + $reservedQuantity;

        $roundingResult = $this->orderRoundingService->round(array_merge($input, [
            'raw_need' => $rawNeed,
        ]));

        $warnings = array_values(array_unique(array_merge($warnings, $roundingResult['warnings'])));
        $requiresHumanReview = $requiresHumanReview || $warnings !== [];
        $status = $requiresHumanReview ? 'needs_review' : 'draft';

        return [
            'formula_version' => self::FORMULA_VERSION,
            'status' => $status,
            'trend' => $this->rounded($trend),
            'need_t0_t1' => $this->rounded($needT0T1),
            'stock_t1' => $this->rounded($stockT1),
            'need_t1_t2' => $this->rounded($needT1T2),
            'safety_stock' => $this->rounded($safetyStock),
            'raw_need' => $this->rounded($rawNeed),
            'recommended_quantity' => $this->rounded((float) $roundingResult['recommended_quantity']),
            'applied_rules' => $roundingResult['applied_rules'],
            'warnings' => $warnings,
            'requires_human_review' => $requiresHumanReview,
            'explanation' => $this->explanation($input, [
                'trend' => $trend,
                'need_t0_t1' => $needT0T1,
                'stock_t1' => $stockT1,
                'need_t1_t2' => $needT1T2,
                'safety_stock' => $safetyStock,
                'reserved_quantity' => $reservedQuantity,
                'raw_need' => $rawNeed,
            ], $roundingResult, $warnings, $status, (float) $roundingResult['recommended_quantity']),
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $trendResult
     * @param  list<string>  $warnings
     * @return array<string, mixed>
     */
    private function reviewResult(array $input, array $trendResult, array $warnings): array
    {
        $intermediateValues = [
            'trend' => null,
            'need_t0_t1' => null,
            'stock_t1' => null,
            'need_t1_t2' => null,
            'safety_stock' => null,
            'reserved_quantity' => $this->reservationQuantity($input),
            'raw_need' => null,
        ];

        return [
            'formula_version' => self::FORMULA_VERSION,
            'status' => 'needs_review',
            'trend' => null,
            'need_t0_t1' => null,
            'stock_t1' => null,
            'need_t1_t2' => null,
            'safety_stock' => null,
            'raw_need' => null,
            'recommended_quantity' => 0.0,
            'applied_rules' => [
                'trend' => [
                    'status' => $trendResult['status'],
                    'applied_fallback' => $trendResult['applied_fallback'],
                ],
            ],
            'warnings' => $warnings,
            'requires_human_review' => true,
            'explanation' => $this->explanation($input, $intermediateValues, [
                'rounding_steps' => [],
            ], $warnings, 'needs_review', 0.0),
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function reservationQuantity(array $input): float
    {
        if (($input['reservation_strategy'] ?? 'include') === 'exclude') {
            return 0.0;
        }

        return $this->number($input, 'reserved_quantity');
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function number(array $input, string $key): float
    {
        return isset($input[$key]) && is_numeric($input[$key]) ? (float) $input[$key] : 0.0;
    }

    private function rounded(float $value): float
    {
        return round($value, 6);
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $intermediateValues
     * @param  array<string, mixed>  $roundingResult
     * @param  list<string>  $warnings
     * @return array<string, mixed>
     */
    private function explanation(
        array $input,
        array $intermediateValues,
        array $roundingResult,
        array $warnings,
        string $status,
        float $recommendedQuantity,
    ): array {
        return [
            'dates' => [
                't0_date' => $input['t0_date'] ?? null,
                't1_date' => $input['t1_date'] ?? null,
                't2_date' => $input['t2_date'] ?? null,
                't3_date' => $input['t3_date'] ?? null,
            ],
            'formula_steps' => [
                'trend = current_year_sales_for_trend / last_year_sales_for_trend',
                'need_t0_t1 = last_year_sales_t0_t1 * trend',
                'stock_t1 = free_stock + inbound_until_t1 - need_t0_t1',
                'need_t1_t2 = last_year_sales_t1_t2 * trend',
                'safety_stock = last_year_sales_t2_t3 * trend',
                'raw_need = need_t1_t2 + safety_stock - stock_t1 - inbound_t1_t3 + reserved_quantity',
            ],
            'input_values' => [
                'company_id' => $input['company_id'] ?? null,
                'supplier_id' => $input['supplier_id'] ?? null,
                'product_id' => $input['product_id'] ?? null,
                'current_year_sales_for_trend' => $this->number($input, 'current_year_sales_for_trend'),
                'last_year_sales_for_trend' => $this->number($input, 'last_year_sales_for_trend'),
                'last_year_sales_t0_t1' => $this->number($input, 'last_year_sales_t0_t1'),
                'last_year_sales_t1_t2' => $this->number($input, 'last_year_sales_t1_t2'),
                'last_year_sales_t2_t3' => $this->number($input, 'last_year_sales_t2_t3'),
                'free_stock' => $this->number($input, 'free_stock'),
                'inbound_until_t1' => $this->number($input, 'inbound_until_t1'),
                'inbound_t1_t3' => $this->number($input, 'inbound_t1_t3'),
                'reserved_quantity' => $this->number($input, 'reserved_quantity'),
                'reservation_strategy' => $input['reservation_strategy'] ?? 'include',
                'safety_days_rule' => $input['safety_days_rule'] ?? null,
            ],
            'intermediate_values' => $intermediateValues,
            'rounding_steps' => $roundingResult['rounding_steps'] ?? [],
            'warnings' => $warnings,
            'final_result' => [
                'status' => $status,
                'recommended_quantity' => $this->rounded($recommendedQuantity),
            ],
        ];
    }
}
