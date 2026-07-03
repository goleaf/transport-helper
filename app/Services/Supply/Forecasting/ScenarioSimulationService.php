<?php

namespace App\Services\Supply\Forecasting;

use App\Enums\CalculationScenarioStatus;
use App\Enums\ScenarioSimulationMode;
use App\Models\CalculationScenario;
use App\Models\Company;
use App\Models\OrderProposalItem;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierProductRule;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\Supply\Calculation\OrderNeedCalculator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Throwable;

class ScenarioSimulationService
{
    public function __construct(
        private readonly RefinedCalculationInputBuilder $inputBuilder,
        private readonly OrderNeedCalculator $orderNeedCalculator,
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * @param  array<string, mixed>  $parameters
     * @return array{scenario: CalculationScenario}
     */
    public function simulate(Company $company, Supplier $supplier, array $parameters, User $user): array
    {
        $scenario = CalculationScenario::query()->create([
            'company_id' => $company->getKey(),
            'supplier_id' => $supplier->getKey(),
            'base_calculation_run_id' => $parameters['base_calculation_run_id'] ?? null,
            'name' => $parameters['name'],
            'status' => CalculationScenarioStatus::Running,
            'simulation_mode' => $this->simulationMode($parameters),
            'formula_version' => 'v1_scenario',
            'parameters_json' => $parameters,
            'warnings_json' => [],
            'errors_json' => [],
            'created_by_user_id' => $user->getKey(),
        ]);

        $this->auditLogService->write('scenario_simulation_started', $scenario, $user, null, [
            'supplier_id' => $supplier->getKey(),
            'parameters' => $this->safeParameters($parameters),
        ], [], $company->getKey());

        try {
            DB::transaction(function () use ($scenario, $company, $supplier, $parameters, $user): void {
                $products = $this->products($company, $supplier, $parameters);
                $baseQuantities = $this->baseQuantities($parameters);
                $warnings = [];
                $totalQuantity = 0.0;
                $totalDifference = 0.0;
                $needsReviewCount = 0;

                if ($products->isEmpty()) {
                    $warnings[] = 'no_products_matched_scenario';
                }

                foreach ($products as $product) {
                    $built = $this->inputBuilder->build($company, $supplier, $product, $parameters, $user);
                    $output = $this->orderNeedCalculator->calculate($built['input']);
                    $base = $baseQuantities[$product->getKey()] ?? null;
                    $simulatedQuantity = (float) ($output['recommended_quantity'] ?? 0);
                    $difference = $base === null ? null : round($simulatedQuantity - (float) $base['recommended_quantity'], 4);
                    $itemWarnings = array_values(array_unique(array_merge($built['warnings'], $output['warnings'] ?? [])));
                    $requiresReview = (bool) ($built['requires_human_review'] ?? false) || (bool) ($output['requires_human_review'] ?? false);

                    $scenario->items()->create([
                        'product_id' => $product->getKey(),
                        'base_order_proposal_item_id' => $base['item_id'] ?? null,
                        'status' => $output['status'] ?? 'simulated',
                        'base_raw_need' => $base['raw_need'] ?? null,
                        'base_recommended_quantity' => $base['recommended_quantity'] ?? null,
                        'simulated_raw_need' => $output['raw_need'] ?? null,
                        'simulated_recommended_quantity' => $output['recommended_quantity'] ?? null,
                        'difference_quantity' => $difference,
                        'trend_used' => $output['trend'] ?? null,
                        'seasonality_factor' => $built['seasonality']['factor'] ?? 1.0,
                        'manual_trend_override_id' => $built['input']['manual_trend_override_id'] ?? null,
                        'applied_profile_id' => $built['applied_rules']['profile_id'] ?? null,
                        'input_json' => $built,
                        'output_json' => $output,
                        'explanation_json' => $built['explanation'],
                        'warnings_json' => $itemWarnings,
                        'requires_human_review' => $requiresReview,
                    ]);

                    $warnings = array_merge($warnings, $itemWarnings);
                    $totalQuantity += $simulatedQuantity;
                    $totalDifference += (float) ($difference ?? 0);
                    $needsReviewCount += $requiresReview ? 1 : 0;
                }

                $warnings = array_values(array_unique($warnings));
                $scenario->forceFill([
                    'status' => $warnings === [] ? CalculationScenarioStatus::Simulated : CalculationScenarioStatus::SimulatedWithWarnings,
                    'summary_json' => [
                        'items_count' => $products->count(),
                        'needs_review_count' => $needsReviewCount,
                        'total_simulated_quantity' => round($totalQuantity, 4),
                        'total_difference_vs_base' => round($totalDifference, 4),
                    ],
                    'warnings_json' => $warnings,
                    'simulated_at' => now(),
                ])->save();
            });

            $scenario->refresh()->load(['items.product:id,sku,name,category', 'supplier:id,name,code']);

            $this->auditLogService->write('scenario_simulation_completed', $scenario, $user, null, [
                'status' => $scenario->status->value,
                'summary' => $scenario->summary_json,
            ], [], $company->getKey());

            return ['scenario' => $scenario];
        } catch (Throwable $exception) {
            $scenario->forceFill([
                'status' => CalculationScenarioStatus::Failed,
                'errors_json' => [
                    'message' => $exception->getMessage(),
                    'type' => $exception::class,
                ],
            ])->save();

            $this->auditLogService->write('scenario_simulation_failed', $scenario, $user, null, [
                'message' => $exception->getMessage(),
                'type' => $exception::class,
            ], [], $company->getKey());

            throw $exception;
        }
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    private function simulationMode(array $parameters): ScenarioSimulationMode
    {
        if (! empty($parameters['product_ids'])) {
            return ScenarioSimulationMode::ProductSet;
        }

        if (! empty($parameters['category'])) {
            return ScenarioSimulationMode::Category;
        }

        return ScenarioSimulationMode::Supplier;
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return EloquentCollection<int, Product>
     */
    private function products(Company $company, Supplier $supplier, array $parameters): EloquentCollection
    {
        $query = Product::query()
            ->select(['id', 'company_id', 'sku', 'manufacturer_sku', 'name', 'category', 'brand', 'unit', 'is_active'])
            ->where('company_id', $company->getKey())
            ->where('is_active', true);

        $productIds = collect($parameters['product_ids'] ?? [])->filter()->map(fn (mixed $id): int => (int) $id)->values()->all();

        if ($productIds !== []) {
            $query->whereIn('id', $productIds);
        } elseif (! empty($parameters['category'])) {
            $query->where('category', $parameters['category']);
        } else {
            $ruleProductIds = SupplierProductRule::query()
                ->select(['id', 'supplier_id', 'product_id', 'order_enabled'])
                ->where('supplier_id', $supplier->getKey())
                ->where('order_enabled', true)
                ->limit(1000)
                ->pluck('product_id')
                ->map(fn (mixed $id): int => (int) $id)
                ->values()
                ->all();

            $query->whereIn('id', $ruleProductIds);
        }

        return $query
            ->orderBy('sku')
            ->orderBy('id')
            ->limit(1000)
            ->get();
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<int, array{item_id:int,raw_need:mixed,recommended_quantity:mixed}>
     */
    private function baseQuantities(array $parameters): array
    {
        $baseRunId = $parameters['base_calculation_run_id'] ?? null;

        if ($baseRunId === null) {
            return [];
        }

        return OrderProposalItem::query()
            ->select(['id', 'order_proposal_id', 'product_id', 'raw_need', 'recommended_quantity'])
            ->whereHas('orderProposal', fn ($query) => $query->where('calculation_run_id', $baseRunId))
            ->orderByDesc('id')
            ->limit(5000)
            ->get()
            ->unique('product_id')
            ->mapWithKeys(fn (OrderProposalItem $item): array => [
                (int) $item->product_id => [
                    'item_id' => (int) $item->getKey(),
                    'raw_need' => $item->raw_need,
                    'recommended_quantity' => $item->recommended_quantity,
                ],
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    private function safeParameters(array $parameters): array
    {
        return collect($parameters)
            ->except(['secrets', 'token', 'api_key'])
            ->all();
    }
}
