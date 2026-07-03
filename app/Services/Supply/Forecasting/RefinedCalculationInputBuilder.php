<?php

namespace App\Services\Supply\Forecasting;

use App\Enums\TrendOverrideStatus;
use App\Models\Company;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\TrendOverride;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\Supply\Calculation\CalculationDataCollector;
use Carbon\CarbonImmutable;

class RefinedCalculationInputBuilder
{
    public function __construct(
        private readonly SalesSeriesService $salesSeriesService,
        private readonly SalesExclusionService $salesExclusionService,
        private readonly SeasonalityFactorService $seasonalityFactorService,
        private readonly TrendOverrideService $trendOverrideService,
        private readonly ReplenishmentRuleResolver $ruleResolver,
        private readonly CalculationDataCollector $calculationDataCollector,
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function build(Company $company, Supplier $supplier, Product $product, array $parameters, ?User $user = null): array
    {
        $parameters = $this->defaultParameters($parameters);
        $scenarioOptions = $parameters['scenario_options'] ?? [];
        $resolved = $this->ruleResolver->resolve($company, $product, $supplier, $parameters);
        $rules = $resolved['rules'];
        $collected = $this->calculationDataCollector->collectForProduct($company, $supplier, $product, $parameters);
        $baseInput = $collected['input'];
        $input = $baseInput;
        $warnings = array_values($collected['warnings'] ?? []);
        $appliedExclusions = [];

        foreach ($this->salesPeriods() as $inputKey => $period) {
            $periodRules = $this->salesExclusionService->matchingRules($company, $product, $parameters[$period['start']], $parameters[$period['end']], [
                'supplier_id' => $supplier->getKey(),
                'applies_to' => $period['applies_to'],
            ]);

            $sum = $this->salesSeriesService->salesSum($company, $product, $parameters[$period['start']], $parameters[$period['end']], [
                'supplier_id' => $supplier->getKey(),
                'exclude_promotions' => $this->optionBool($scenarioOptions, 'exclude_promotions', (bool) $rules['exclude_promotions']),
                'exclude_anomalies' => $this->optionBool($scenarioOptions, 'exclude_anomalies', (bool) $rules['exclude_anomalies']),
                'exclusion_rule_ids' => collect($periodRules)->pluck('id')->all(),
                'outlier_detection' => $this->optionBool($scenarioOptions, 'outlier_detection', (bool) $rules['outlier_detection_enabled']),
                'exclude_outlier_candidates' => $this->optionBool($scenarioOptions, 'exclude_outlier_candidates', false),
                'outlier_multiplier' => $scenarioOptions['outlier_multiplier'] ?? $rules['outlier_multiplier'],
            ]);

            $input[$inputKey] = (float) $sum['sum'];
            $warnings = array_merge($warnings, $sum['warnings']);
            $appliedExclusions[$inputKey] = [
                'period' => $period['applies_to'],
                'date_from' => $parameters[$period['start']],
                'date_to' => $parameters[$period['end']],
                'sum' => $sum,
                'rules' => $this->salesExclusionService->explainExclusions($periodRules)['rules'],
            ];
        }

        $seasonality = $this->seasonality($company, $product, $parameters, $scenarioOptions, $rules);
        $warnings = array_merge($warnings, $seasonality['warnings'] ?? []);
        $input = $this->applySeasonality($input, $seasonality);

        $trendOverride = $this->applyTrendOverride($company, $supplier, $product, $parameters, $scenarioOptions, $input, $warnings, $user);
        $input = $trendOverride['input'];
        $warnings = $trendOverride['warnings'];

        $input['formula_version'] = 'v1_refined';
        $input['reservation_strategy'] = $scenarioOptions['reservation_strategy'] ?? $rules['reservation_strategy'];
        $input['pallet_strategy'] = $scenarioOptions['pallet_strategy'] ?? $rules['pallet_strategy'];
        $input['transport_strategy'] = $scenarioOptions['transport_strategy'] ?? $rules['transport_strategy'];
        $input['rounding_strategy'] = [
            'pallet' => $input['pallet_strategy'],
            'transport' => $input['transport_strategy'],
        ];
        $input['strategic_minimum_order_enabled'] = $this->optionBool($scenarioOptions, 'strategic_minimum_order_enabled', (bool) $rules['strategic_minimum_order_enabled']);
        $input['lead_time_days_override'] = $scenarioOptions['lead_time_days_override'] ?? $rules['lead_time_days_override'];
        $input['safety_days_override'] = $scenarioOptions['safety_days_override'] ?? $rules['safety_days_override'];

        $safetyMultiplier = (float) ($scenarioOptions['safety_stock_multiplier'] ?? $rules['safety_stock_multiplier'] ?? 1.0);
        if ($safetyMultiplier !== 1.0) {
            $input['last_year_sales_t2_t3'] = round((float) $input['last_year_sales_t2_t3'] * $safetyMultiplier, 4);
        }

        $requiresReview = (bool) ($trendOverride['requires_human_review'] ?? false) || $warnings !== [];

        return [
            'input' => $input,
            'base_input' => $baseInput,
            'applied_rules' => [
                'profile_id' => $resolved['profile']?->getKey(),
                'rules' => $rules,
                'resolution' => $resolved['explanation'],
            ],
            'applied_exclusions' => $appliedExclusions,
            'seasonality' => $seasonality,
            'trend_override' => $trendOverride['override'],
            'warnings' => array_values(array_unique($warnings)),
            'requires_human_review' => $requiresReview,
            'explanation' => [
                'base_formula_version' => 'v1',
                'refined_formula_version' => 'v1_refined',
                'collector_source' => $collected['source'] ?? [],
                'profile_resolution' => $resolved['explanation'],
                'sales_exclusions' => $appliedExclusions,
                'seasonality' => $seasonality,
                'manual_trend_override' => $trendOverride['override'],
                'safety_multiplier' => $safetyMultiplier,
                'final_input_changes' => $this->changedValues($baseInput, $input),
            ],
        ];
    }

    /**
     * @return array<string, array{start:string,end:string,applies_to:string}>
     */
    private function salesPeriods(): array
    {
        return [
            'current_year_sales_for_trend' => ['start' => 'trend_current_start', 'end' => 'trend_current_end', 'applies_to' => 'trend_period'],
            'last_year_sales_for_trend' => ['start' => 'trend_last_start', 'end' => 'trend_last_end', 'applies_to' => 'trend_period'],
            'last_year_sales_t0_t1' => ['start' => 'last_year_t0_t1_start', 'end' => 'last_year_t0_t1_end', 'applies_to' => 't0_t1'],
            'last_year_sales_t1_t2' => ['start' => 'last_year_t1_t2_start', 'end' => 'last_year_t1_t2_end', 'applies_to' => 't1_t2'],
            'last_year_sales_t2_t3' => ['start' => 'last_year_t2_t3_start', 'end' => 'last_year_t2_t3_end', 'applies_to' => 't2_t3'],
        ];
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    private function defaultParameters(array $parameters): array
    {
        $t0 = CarbonImmutable::parse($parameters['t0_date']);
        $t1 = CarbonImmutable::parse($parameters['t1_date']);
        $t2 = CarbonImmutable::parse($parameters['t2_date']);
        $t3 = CarbonImmutable::parse($parameters['t3_date']);
        $trendCurrentStart = $t0->subMonthsNoOverflow(3);

        return $parameters + [
            'trend_current_start' => $trendCurrentStart->toDateString(),
            'trend_current_end' => $t0->toDateString(),
            'trend_last_start' => $trendCurrentStart->subYear()->toDateString(),
            'trend_last_end' => $t0->subYear()->toDateString(),
            'last_year_t0_t1_start' => $t0->subYear()->toDateString(),
            'last_year_t0_t1_end' => $t1->subYear()->toDateString(),
            'last_year_t1_t2_start' => $t1->subYear()->toDateString(),
            'last_year_t1_t2_end' => $t2->subYear()->toDateString(),
            'last_year_t2_t3_start' => $t2->subYear()->toDateString(),
            'last_year_t2_t3_end' => $t3->subYear()->toDateString(),
            'reservation_strategy' => 'reserved_not_removed_from_free_stock',
            'rounding_strategy' => [
                'pallet' => 'show_only',
                'transport' => 'show_only',
            ],
            'strategic_minimum_order_enabled' => false,
        ];
    }

    /**
     * @param  array<string, mixed>  $scenarioOptions
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    private function seasonality(Company $company, Product $product, array $parameters, array $scenarioOptions, array $rules): array
    {
        $enabled = $this->optionBool($scenarioOptions, 'use_seasonality', (bool) $rules['seasonality_enabled']);
        $mode = $scenarioOptions['seasonality_mode'] ?? $rules['seasonality_mode'] ?? 'none';

        if (! $enabled || $mode === 'none') {
            return [
                'enabled' => false,
                'mode' => 'none',
                'factor' => 1.0,
                'warnings' => [],
                'explanation' => ['calculation' => 'seasonality disabled'],
            ];
        }

        $factor = $this->seasonalityFactorService->calculateFactor($company, $product, $parameters['t0_date'], $parameters['t1_date']);

        return $factor + [
            'enabled' => true,
            'mode' => $mode,
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $seasonality
     * @return array<string, mixed>
     */
    private function applySeasonality(array $input, array $seasonality): array
    {
        $factor = (float) ($seasonality['factor'] ?? 1.0);
        $mode = $seasonality['mode'] ?? 'none';

        if ($factor === 1.0 || $mode === 'none') {
            return $input;
        }

        if ($mode === 'multiply_trend') {
            $input['current_year_sales_for_trend'] = round((float) $input['current_year_sales_for_trend'] * $factor, 4);
        }

        if ($mode === 'multiply_period_sales') {
            foreach (['last_year_sales_t0_t1', 'last_year_sales_t1_t2', 'last_year_sales_t2_t3'] as $key) {
                $input[$key] = round((float) $input[$key] * $factor, 4);
            }
        }

        return $input;
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @param  array<string, mixed>  $scenarioOptions
     * @param  array<string, mixed>  $input
     * @param  list<string>  $warnings
     * @return array{input: array<string, mixed>, warnings: list<string>, override: array<string, mixed>|null, requires_human_review: bool}
     */
    private function applyTrendOverride(Company $company, Supplier $supplier, Product $product, array $parameters, array $scenarioOptions, array $input, array $warnings, ?User $user): array
    {
        if (! $this->optionBool($scenarioOptions, 'use_manual_overrides', true)) {
            return [
                'input' => $input,
                'warnings' => $warnings,
                'override' => null,
                'requires_human_review' => false,
            ];
        }

        $date = $parameters['t0_date'];
        $pendingExists = TrendOverride::query()
            ->where('company_id', $company->getKey())
            ->whereIn('status', [TrendOverrideStatus::Draft, TrendOverrideStatus::PendingApproval])
            ->whereDate('date_from', '<=', $date)
            ->whereDate('date_to', '>=', $date)
            ->where(function ($query) use ($product): void {
                $query->whereNull('product_id')->orWhere('product_id', $product->getKey());
            })
            ->where(function ($query) use ($product): void {
                $query->whereNull('category')->orWhere('category', $product->category);
            })
            ->exists();

        if ($pendingExists) {
            $warnings[] = 'unapproved_trend_override_exists_not_used';
        }

        $applicable = $this->trendOverrideService->findApplicable($company, $product, $supplier, $date);
        $override = $applicable['override'];

        if (! $override instanceof TrendOverride) {
            return [
                'input' => $input,
                'warnings' => $warnings,
                'override' => null,
                'requires_human_review' => $pendingExists,
            ];
        }

        $input['current_year_sales_for_trend'] = (float) $override->trend_value;
        $input['last_year_sales_for_trend'] = 1.0;
        $input['manual_trend_override_id'] = $override->getKey();

        if ($user instanceof User) {
            $this->auditLogService->write('trend_override_used', $override, $user, null, [
                'company_id' => $company->getKey(),
                'supplier_id' => $supplier->getKey(),
                'product_id' => $product->getKey(),
                'scenario_date' => $date,
            ], [], $company->getKey());
        }

        return [
            'input' => $input,
            'warnings' => $warnings,
            'override' => [
                'id' => $override->getKey(),
                'trend_value' => (float) $override->trend_value,
                'reason' => $override->reason,
                'approved_by_user_id' => $override->approved_by_user_id,
                'approved_at' => $override->approved_at?->toISOString(),
            ],
            'requires_human_review' => false,
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function optionBool(array $options, string $key, bool $default): bool
    {
        return array_key_exists($key, $options) ? filter_var($options[$key], FILTER_VALIDATE_BOOL) : $default;
    }

    /**
     * @param  array<string, mixed>  $baseInput
     * @param  array<string, mixed>  $input
     * @return array<string, array{before:mixed,after:mixed}>
     */
    private function changedValues(array $baseInput, array $input): array
    {
        return collect($input)
            ->filter(fn (mixed $value, string $key): bool => array_key_exists($key, $baseInput) && $baseInput[$key] !== $value)
            ->map(fn (mixed $value, string $key): array => [
                'before' => $baseInput[$key],
                'after' => $value,
            ])
            ->all();
    }
}
