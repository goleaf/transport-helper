<?php

namespace App\Services\Supply\Procurement;

use App\Models\CalculationScenario;
use App\Models\Company;
use App\Models\OrderProposal;
use App\Models\Supplier;
use App\Models\SupplierOrder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class ProcurementComplianceService
{
    public function __construct(
        private readonly OrderValueEstimationService $estimationService,
        private readonly ProcurementPolicyResolver $policyResolver,
        private readonly BudgetAvailabilityService $budgetAvailabilityService,
        private readonly ApprovalRequirementService $approvalRequirementService,
        private readonly ProcurementApprovalWorkflowService $approvalWorkflowService,
        private readonly ProcurementExceptionService $exceptionService,
        private readonly SupplierOrderRuleService $supplierOrderRuleService,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function check(Model $model, array $options = []): array
    {
        $context = $this->context($model, $options);
        $company = $context['company'];
        $supplier = $context['supplier'];
        $estimation = $this->estimate($model, $options);
        $policy = $this->policyResolver->resolve($company, $supplier, $context);
        $budgetCheck = $this->budgetAvailabilityService->check($company, $estimation, $context);
        $approvalRequirements = $this->approvalRequirementService->determine($policy, $estimation, $budgetCheck, $context);
        $approvalState = $this->approvalWorkflowService->hasSufficientApproval($model, $approvalRequirements['requirements']);
        $supplierRules = $supplier instanceof Supplier
            ? $this->supplierOrderRuleService->checkSupplierRules($supplier, $estimation, $policy, $context)
            : ['status' => 'ok', 'warnings' => [], 'blocking_reasons' => [], 'checks' => []];
        $approvedExceptionTypes = $this->approvedExceptionTypes($model, array_merge(
            $budgetCheck['blocking_reasons'] ?? [],
            $supplierRules['blocking_reasons'] ?? [],
            ($estimation['missing_price_count'] ?? 0) > 0 ? ['missing_price'] : [],
        ));
        $warnings = array_values(array_unique(array_merge(
            $policy['warnings'] ?? [],
            $estimation['warnings'] ?? [],
            $budgetCheck['warnings'] ?? [],
            $approvalRequirements['warnings'] ?? [],
            $approvalState['warnings'] ?? [],
            $supplierRules['warnings'] ?? [],
        )));

        return [
            'model_type' => $model::class,
            'model_id' => $model->getKey(),
            'company' => ['id' => $company->getKey(), 'name' => $company->name],
            'supplier' => $supplier instanceof Supplier ? ['id' => $supplier->getKey(), 'name' => $supplier->name] : null,
            'estimated_value' => $estimation,
            'policy' => $policy,
            'budget_check' => $budgetCheck,
            'approval_requirements' => $approvalRequirements,
            'approval_state' => $approvalState,
            'supplier_rules' => $supplierRules,
            'exceptions' => [
                'approved_types' => $approvedExceptionTypes,
            ],
            'warnings' => $warnings,
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function estimate(Model $model, array $options): array
    {
        if ($model instanceof OrderProposal) {
            return $this->estimationService->estimateProposal($model, $options);
        }

        if ($model instanceof SupplierOrder) {
            return $this->estimationService->estimateSupplierOrder($model, $options);
        }

        if ($model instanceof CalculationScenario) {
            return $this->estimateScenario($model, $options);
        }

        throw new InvalidArgumentException('Unsupported procurement compliance model.');
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function estimateScenario(CalculationScenario $scenario, array $options): array
    {
        $scenario->loadMissing(['items.product:id,company_id,sku,name,category']);
        $priceMap = $options['price_map'] ?? [];
        $currency = (string) ($options['currency'] ?? config('supply.procurement.default_currency', 'EUR'));
        $lines = [];
        $total = 0.0;
        $missing = 0;

        foreach ($scenario->items as $item) {
            $price = is_array($priceMap) ? ($priceMap[$item->product_id]['unit_price'] ?? $priceMap[$item->product_id] ?? null) : null;
            $quantity = (float) $item->simulated_recommended_quantity;
            $lineTotal = is_numeric($price) ? round($quantity * (float) $price, 4) : null;
            if ($lineTotal === null) {
                $missing++;
            } else {
                $total += $lineTotal;
            }

            $lines[] = [
                'product_id' => $item->product_id,
                'sku' => $item->product?->sku,
                'quantity' => $quantity,
                'unit_price' => $price,
                'line_total' => $lineTotal,
                'currency' => $currency,
                'price_source' => $lineTotal === null ? 'missing' : 'fallback',
                'warnings' => $lineTotal === null ? ['missing_price'] : ['fallback_price_used'],
            ];
        }

        return [
            'total' => round($total, 4),
            'currency' => $currency,
            'confidence' => $missing > 0 ? 'low' : 'low',
            'lines' => $lines,
            'missing_price_count' => $missing,
            'warnings' => $missing > 0 ? ['missing_price'] : ['fallback_price_used'],
            'requires_human_review' => true,
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>&array{company: Company, supplier: Supplier|null}
     */
    private function context(Model $model, array $options): array
    {
        if ($model instanceof OrderProposal) {
            $model->loadMissing(['company:id,name,default_currency', 'supplier:id,company_id,name,default_currency', 'items:id,order_proposal_id,product_id,recommended_quantity,approved_quantity', 'items.product:id,company_id,category']);

            return [
                'company' => $model->company,
                'supplier' => $model->supplier,
                'supplier_id' => $model->supplier_id,
                'product_ids' => $model->items->pluck('product_id')->map(fn (mixed $id): int => (int) $id)->all(),
                'category' => $options['category'] ?? $model->items->pluck('product.category')->filter()->first(),
                'date' => $options['date'] ?? now()->toDateString(),
            ];
        }

        if ($model instanceof SupplierOrder) {
            $model->loadMissing(['company:id,name,default_currency', 'supplier:id,company_id,name,default_currency', 'items:id,supplier_order_id,product_id,ordered_quantity,unit_price,currency', 'items.product:id,company_id,category']);

            return [
                'company' => $model->company,
                'supplier' => $model->supplier,
                'supplier_id' => $model->supplier_id,
                'product_ids' => $model->items->pluck('product_id')->map(fn (mixed $id): int => (int) $id)->all(),
                'category' => $options['category'] ?? $model->items->pluck('product.category')->filter()->first(),
                'date' => $options['date'] ?? $model->order_date?->toDateString() ?? now()->toDateString(),
            ];
        }

        if ($model instanceof CalculationScenario) {
            $model->loadMissing(['company:id,name,default_currency', 'supplier:id,company_id,name,default_currency', 'items:id,calculation_scenario_id,product_id,simulated_recommended_quantity', 'items.product:id,company_id,category']);

            return [
                'company' => $model->company,
                'supplier' => $model->supplier,
                'supplier_id' => $model->supplier_id,
                'product_ids' => $model->items->pluck('product_id')->map(fn (mixed $id): int => (int) $id)->all(),
                'category' => $options['category'] ?? $model->items->pluck('product.category')->filter()->first(),
                'date' => $options['date'] ?? now()->toDateString(),
            ];
        }

        throw new InvalidArgumentException('Unsupported procurement context model.');
    }

    /**
     * @param  list<string>  $types
     * @return list<string>
     */
    private function approvedExceptionTypes(Model $model, array $types): array
    {
        return collect($types)
            ->filter(fn (string $type): bool => $this->exceptionService->hasApprovedException($model, [$type]))
            ->values()
            ->all();
    }
}
