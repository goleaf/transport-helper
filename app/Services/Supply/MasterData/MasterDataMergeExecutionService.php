<?php

namespace App\Services\Supply\MasterData;

use App\Enums\MasterDataMergeStatus;
use App\Enums\ProductLifecycleStatus;
use App\Enums\SupplierLifecycleStatus;
use App\Models\CalculationScenarioItem;
use App\Models\InboundOrder;
use App\Models\InboundOrderItem;
use App\Models\LogisticsRecord;
use App\Models\MasterDataMergeProposal;
use App\Models\OrderProposal;
use App\Models\OrderProposalItem;
use App\Models\ProcurementBudgetLine;
use App\Models\Product;
use App\Models\ProductAlias;
use App\Models\Reservation;
use App\Models\SalesHistory;
use App\Models\StockSnapshot;
use App\Models\Supplier;
use App\Models\SupplierAlias;
use App\Models\SupplierConfirmation;
use App\Models\SupplierConfirmationItem;
use App\Models\SupplierContact;
use App\Models\SupplierOrder;
use App\Models\SupplierOrderItem;
use App\Models\SupplierProductIdentity;
use App\Models\SupplierProductPrice;
use App\Models\SupplierProductRule;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class MasterDataMergeExecutionService
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    /**
     * @param  array<string, mixed>  $options
     * @return array{proposal: MasterDataMergeProposal, result: array<string,mixed>}
     */
    public function execute(MasterDataMergeProposal $proposal, User $user, array $options = []): array
    {
        if ($proposal->status !== MasterDataMergeStatus::Approved) {
            throw new InvalidArgumentException('Merge proposal must be approved before execution.');
        }

        $result = DB::transaction(function () use ($proposal, $user): array {
            $result = match ($proposal->merge_type) {
                'product' => $this->executeProductMerge($proposal, $user),
                'supplier' => $this->executeSupplierMerge($proposal, $user),
                default => throw new InvalidArgumentException('Unsupported merge type.'),
            };

            $old = $proposal->getOriginal();
            $proposal->forceFill([
                'status' => MasterDataMergeStatus::Executed,
                'executed_by_user_id' => $user->getKey(),
                'executed_at' => now(),
                'execution_result_json' => $result,
            ])->save();

            $this->auditLogService->write('master_data_merge_executed', $proposal, $user, $old, $proposal->getChanges(), [
                'merge_type' => $proposal->merge_type,
                'skipped' => $result['skipped'] ?? [],
            ], $proposal->company_id);

            return $result;
        });

        return ['proposal' => $proposal->refresh(), 'result' => $result];
    }

    /**
     * @return array<string,mixed>
     */
    private function executeProductMerge(MasterDataMergeProposal $proposal, User $user): array
    {
        $source = Product::query()->findOrFail($proposal->source_model_id);
        $target = Product::query()->findOrFail($proposal->target_model_id);
        $updated = [];
        $skipped = [];

        SupplierProductRule::query()
            ->where('product_id', $source->getKey())
            ->get()
            ->each(function (SupplierProductRule $rule) use ($target, &$updated, &$skipped): void {
                $conflict = SupplierProductRule::query()
                    ->where('supplier_id', $rule->supplier_id)
                    ->where('product_id', $target->getKey())
                    ->exists();

                if ($conflict) {
                    $skipped[] = ['table' => 'supplier_product_rules', 'id' => $rule->id, 'reason' => 'unique_supplier_product_conflict'];

                    return;
                }

                $rule->forceFill(['product_id' => $target->getKey()])->save();
                $updated['supplier_product_rules'] = ($updated['supplier_product_rules'] ?? 0) + 1;
            });

        foreach ($this->productReferenceModels() as $table => $model) {
            if (! Schema::hasColumn((new $model)->getTable(), 'product_id')) {
                $skipped[] = ['table' => $table, 'reason' => 'missing_product_id_column'];

                continue;
            }

            $count = $model::query()->where('product_id', $source->getKey())->update(['product_id' => $target->getKey()]);
            $updated[$table] = $count;
        }

        foreach (array_values(array_filter([$source->sku, $source->manufacturer_sku])) as $alias) {
            ProductAlias::query()->firstOrCreate([
                'company_id' => $source->company_id,
                'alias' => $alias,
                'alias_type' => 'merged_source_sku',
            ], [
                'product_id' => $target->getKey(),
                'source_type' => 'merge',
                'source_reference' => (string) $proposal->getKey(),
                'status' => 'active',
                'confidence' => 1.0,
                'reason' => $proposal->reason,
                'approved_by_user_id' => $user->getKey(),
                'approved_at' => now(),
                'created_by_user_id' => $user->getKey(),
            ]);
        }

        $source->forceFill([
            'lifecycle_status' => ProductLifecycleStatus::Merged->value,
            'lifecycle_reason' => $proposal->reason,
            'merged_into_product_id' => $target->getKey(),
            'is_active' => false,
        ])->save();

        return [
            'type' => 'product',
            'source_id' => $source->getKey(),
            'target_id' => $target->getKey(),
            'updated' => $updated,
            'skipped' => $skipped,
            'source_hard_deleted' => false,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function executeSupplierMerge(MasterDataMergeProposal $proposal, User $user): array
    {
        $source = Supplier::query()->findOrFail($proposal->source_model_id);
        $target = Supplier::query()->findOrFail($proposal->target_model_id);
        $updated = [];
        $skipped = [];

        SupplierProductRule::query()
            ->where('supplier_id', $source->getKey())
            ->get()
            ->each(function (SupplierProductRule $rule) use ($target, &$updated, &$skipped): void {
                $conflict = SupplierProductRule::query()
                    ->where('supplier_id', $target->getKey())
                    ->where('product_id', $rule->product_id)
                    ->exists();

                if ($conflict) {
                    $skipped[] = ['table' => 'supplier_product_rules', 'id' => $rule->id, 'reason' => 'unique_supplier_product_conflict'];

                    return;
                }

                $rule->forceFill(['supplier_id' => $target->getKey()])->save();
                $updated['supplier_product_rules'] = ($updated['supplier_product_rules'] ?? 0) + 1;
            });

        foreach ($this->supplierReferenceModels() as $table => $model) {
            if (! Schema::hasColumn((new $model)->getTable(), 'supplier_id')) {
                $skipped[] = ['table' => $table, 'reason' => 'missing_supplier_id_column'];

                continue;
            }

            $count = $model::query()->where('supplier_id', $source->getKey())->update(['supplier_id' => $target->getKey()]);
            $updated[$table] = $count;
        }

        $contactCount = SupplierContact::query()->where('supplier_id', $source->getKey())->update(['supplier_id' => $target->getKey()]);
        $updated['supplier_contacts'] = $contactCount;

        foreach (array_values(array_filter([$source->name, $source->code])) as $alias) {
            SupplierAlias::query()->firstOrCreate([
                'company_id' => $source->company_id,
                'alias' => $alias,
                'alias_type' => 'merged_source_name',
            ], [
                'supplier_id' => $target->getKey(),
                'source_type' => 'merge',
                'source_reference' => (string) $proposal->getKey(),
                'status' => 'active',
                'confidence' => 1.0,
                'reason' => $proposal->reason,
                'approved_by_user_id' => $user->getKey(),
                'approved_at' => now(),
                'created_by_user_id' => $user->getKey(),
            ]);
        }

        $source->forceFill([
            'lifecycle_status' => SupplierLifecycleStatus::Merged->value,
            'lifecycle_reason' => $proposal->reason,
            'merged_into_supplier_id' => $target->getKey(),
            'is_active' => false,
        ])->save();

        return [
            'type' => 'supplier',
            'source_id' => $source->getKey(),
            'target_id' => $target->getKey(),
            'updated' => $updated,
            'skipped' => $skipped,
            'source_hard_deleted' => false,
        ];
    }

    /**
     * @return array<string,class-string<Model>>
     */
    private function productReferenceModels(): array
    {
        return [
            'stock_snapshots' => StockSnapshot::class,
            'sales_history' => SalesHistory::class,
            'inbound_order_items' => InboundOrderItem::class,
            'reservations' => Reservation::class,
            'order_proposal_items' => OrderProposalItem::class,
            'supplier_order_items' => SupplierOrderItem::class,
            'supplier_confirmation_items' => SupplierConfirmationItem::class,
            'supplier_product_identities' => SupplierProductIdentity::class,
            'supplier_product_prices' => SupplierProductPrice::class,
            'procurement_budget_lines' => ProcurementBudgetLine::class,
            'calculation_scenario_items' => CalculationScenarioItem::class,
        ];
    }

    /**
     * @return array<string,class-string<Model>>
     */
    private function supplierReferenceModels(): array
    {
        return [
            'inbound_orders' => InboundOrder::class,
            'order_proposals' => OrderProposal::class,
            'supplier_orders' => SupplierOrder::class,
            'supplier_confirmations' => SupplierConfirmation::class,
            'logistics_records' => LogisticsRecord::class,
            'supplier_product_identities' => SupplierProductIdentity::class,
            'supplier_product_prices' => SupplierProductPrice::class,
            'procurement_budget_lines' => ProcurementBudgetLine::class,
        ];
    }
}
