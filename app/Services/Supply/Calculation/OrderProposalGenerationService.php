<?php

namespace App\Services\Supply\Calculation;

use App\Enums\OrderProposalItemStatus;
use App\Enums\OrderProposalStatus;
use App\Models\CalculationRun;
use App\Models\Company;
use App\Models\OrderProposal;
use App\Models\OrderProposalItem;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierProductRule;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class OrderProposalGenerationService
{
    public function __construct(
        private readonly CalculationDataCollector $dataCollector,
        private readonly OrderNeedCalculator $orderNeedCalculator,
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function generateForSupplier(Company $company, Supplier $supplier, array $parameters, ?User $user = null): array
    {
        return DB::transaction(function () use ($company, $supplier, $parameters, $user): array {
            $warnings = [];
            $now = now();

            $run = CalculationRun::query()->create([
                'company_id' => $company->id,
                'supplier_id' => $supplier->id,
                'calculation_date' => $parameters['calculation_date'] ?? $parameters['t0_date'] ?? $now->toDateString(),
                'formula_version' => OrderNeedCalculator::FORMULA_VERSION,
                'parameters_json' => $parameters,
                'status' => 'processing',
                'started_by_user_id' => $user?->id,
                'started_at' => $now,
                'finished_at' => null,
            ]);

            $proposal = OrderProposal::query()->create([
                'company_id' => $company->id,
                'calculation_run_id' => $run->id,
                'supplier_id' => $supplier->id,
                'status' => OrderProposalStatus::Draft,
                'total_lines' => 0,
                'created_by_user_id' => $user?->id,
                'approved_by_user_id' => null,
                'approved_at' => null,
                'notes' => $parameters['notes'] ?? null,
            ]);

            $rules = $this->supplierRules($company, $supplier, $parameters);
            $needsReviewCount = 0;

            foreach ($rules as $rule) {
                $product = $rule->product;

                if (! $product instanceof Product) {
                    continue;
                }

                $collected = $this->dataCollector->collectForProduct($company, $supplier, $product, $parameters);
                $result = $this->orderNeedCalculator->calculate($collected['input']);
                $itemNeedsReview = $result['status'] === 'needs_review' || (bool) $result['requires_human_review'];

                if ($itemNeedsReview) {
                    $needsReviewCount++;
                }

                $warnings = array_merge($warnings, $collected['warnings'], $result['warnings']);

                $item = OrderProposalItem::query()->create([
                    'order_proposal_id' => $proposal->id,
                    'product_id' => $product->id,
                    't0_date' => $collected['input']['t0_date'],
                    't1_date' => $collected['input']['t1_date'],
                    't2_date' => $collected['input']['t2_date'],
                    't3_date' => $collected['input']['t3_date'],
                    'trend' => $result['trend'],
                    'need_t0_t1' => $result['need_t0_t1'],
                    'stock_t1' => $result['stock_t1'],
                    'need_t1_t2' => $result['need_t1_t2'],
                    'safety_stock' => $result['safety_stock'],
                    'inbound_until_t1' => $result['inbound_until_t1'],
                    'inbound_t1_t3' => $result['inbound_t1_t3'],
                    'reserved_quantity' => $result['reserved_quantity'],
                    'raw_need' => $result['raw_need'],
                    'moq_applied' => $this->appliedRuleValue($result, 'moq'),
                    'pack_multiple_applied' => $this->appliedRuleValue($result, 'pack_multiple'),
                    'pallet_quantity_applied' => $this->appliedRuleValue($result, 'pallet_quantity'),
                    'recommended_quantity' => $result['recommended_quantity'],
                    'approved_quantity' => null,
                    'user_adjusted_quantity' => null,
                    'adjustment_reason' => null,
                    'explanation_json' => $result['explanation'],
                    'warnings_json' => array_values(array_unique(array_merge($collected['warnings'], $result['warnings']))),
                    'requires_human_review' => $itemNeedsReview,
                    'status' => $itemNeedsReview ? OrderProposalItemStatus::NeedsReview : OrderProposalItemStatus::Draft,
                ]);

                $this->auditLogService->logOrderProposalItemCalculated($item, $user, [
                    'source' => $collected['source'],
                    'formula_version' => OrderNeedCalculator::FORMULA_VERSION,
                ]);
            }

            $proposal->forceFill([
                'total_lines' => $proposal->items()->count(),
                'status' => $needsReviewCount > 0 ? OrderProposalStatus::NeedsReview : OrderProposalStatus::Draft,
            ])->save();

            $runStatus = match (true) {
                $proposal->total_lines === 0 => 'failed',
                $warnings !== [] => 'completed_with_warnings',
                default => 'completed',
            };

            $run->forceFill([
                'status' => $runStatus,
                'finished_at' => now(),
            ])->save();

            $uniqueWarnings = array_values(array_unique($warnings));

            $this->auditLogService->logCalculationRun($run, $user, [
                'items_count' => $proposal->total_lines,
                'needs_review_count' => $needsReviewCount,
                'warnings' => $uniqueWarnings,
            ]);

            $this->auditLogService->logOrderProposalCreated($proposal, $user, [
                'calculation_run_id' => $run->id,
                'items_count' => $proposal->total_lines,
                'needs_review_count' => $needsReviewCount,
            ]);

            return [
                'calculation_run' => $run->refresh(),
                'order_proposal' => $proposal->load(['items.product', 'supplier', 'calculationRun']),
                'items_count' => $proposal->total_lines,
                'needs_review_count' => $needsReviewCount,
                'warnings' => $uniqueWarnings,
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    private function supplierRules(Company $company, Supplier $supplier, array $parameters): Collection
    {
        $query = SupplierProductRule::query()
            ->select([
                'id',
                'supplier_id',
                'product_id',
                'supplier_sku',
                'moq',
                'pack_multiple',
                'pallet_quantity',
                'min_transport_quantity',
                'lead_time_days',
                'safety_days',
                'safety_rule_type',
                'transport_rule_type',
                'order_enabled',
            ])
            ->with(['product' => fn ($query) => $query->select(['id', 'company_id', 'sku', 'name', 'is_active'])])
            ->where('supplier_id', $supplier->id)
            ->where('order_enabled', true)
            ->whereHas('product', fn ($query) => $query
                ->where('company_id', $company->id)
                ->where('is_active', true));

        if (! empty($parameters['product_ids']) && is_array($parameters['product_ids'])) {
            $query->whereIn('product_id', $parameters['product_ids']);
        }

        return $query->orderBy('id')->get();
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function appliedRuleValue(array $result, string $ruleName): ?float
    {
        foreach ($result['applied_rules'] as $rule) {
            if (($rule['rule'] ?? null) === $ruleName && isset($rule['rule_value']) && is_numeric($rule['rule_value'])) {
                return (float) $rule['rule_value'];
            }
        }

        return null;
    }
}
