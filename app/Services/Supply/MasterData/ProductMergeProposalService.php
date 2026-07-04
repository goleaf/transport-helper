<?php

namespace App\Services\Supply\MasterData;

use App\Enums\MasterDataMergeStatus;
use App\Models\MasterDataMergeProposal;
use App\Models\Product;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use InvalidArgumentException;

class ProductMergeProposalService
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    /**
     * @return array{proposal: MasterDataMergeProposal}
     */
    public function createProposal(Product $source, Product $target, User $user, string $reason): array
    {
        $this->validatePair($source, $target, $reason);

        $proposal = MasterDataMergeProposal::query()->create([
            'company_id' => $source->company_id,
            'merge_type' => 'product',
            'source_model_type' => Product::class,
            'source_model_id' => $source->getKey(),
            'target_model_type' => Product::class,
            'target_model_id' => $target->getKey(),
            'status' => MasterDataMergeStatus::Draft,
            'reason' => $reason,
            'impact_json' => [],
            'proposed_by_user_id' => $user->getKey(),
        ]);
        $proposal->forceFill(['impact_json' => $this->preview($proposal)])->save();

        $this->auditLogService->write('master_data_merge_proposal_created', $proposal, $user, null, [
            'merge_type' => 'product',
            'source_product_id' => $source->id,
            'target_product_id' => $target->id,
        ], [], $proposal->company_id);

        return ['proposal' => $proposal->refresh()];
    }

    /**
     * @return array<string,mixed>
     */
    public function preview(MasterDataMergeProposal $proposal): array
    {
        $source = Product::query()->findOrFail($proposal->source_model_id);
        $target = Product::query()->findOrFail($proposal->target_model_id);

        return [
            'source' => ['id' => $source->id, 'sku' => $source->sku, 'name' => $source->name],
            'target' => ['id' => $target->id, 'sku' => $target->sku, 'name' => $target->name],
            'affected_tables' => [
                'supplier_product_rules' => $source->supplierProductRules()->count(),
                'stock_snapshots' => $source->stockSnapshots()->count(),
                'sales_history' => $source->salesHistory()->count(),
                'inbound_order_items' => $source->inboundOrderItems()->count(),
                'reservations' => $source->reservations()->count(),
                'order_proposal_items' => $source->orderProposalItems()->count(),
                'supplier_order_items' => $source->supplierOrderItems()->count(),
                'supplier_confirmation_items' => $source->supplierConfirmationItems()->count(),
                'aliases' => $source->aliases()->count(),
            ],
            'aliases_to_create' => array_values(array_filter([$source->sku, $source->manufacturer_sku])),
            'field_differences' => [
                'sku' => [$source->sku, $target->sku],
                'manufacturer_sku' => [$source->manufacturer_sku, $target->manufacturer_sku],
                'name' => [$source->name, $target->name],
                'brand' => [$source->brand, $target->brand],
                'category' => [$source->category, $target->category],
            ],
            'risks' => ['Supplier product rule unique conflicts are skipped and reported.'],
        ];
    }

    /**
     * @return array{proposal: MasterDataMergeProposal}
     */
    public function approve(MasterDataMergeProposal $proposal, User $user, string $note): array
    {
        $this->requireReason($note);
        $old = $proposal->getOriginal();

        $proposal->forceFill([
            'status' => MasterDataMergeStatus::Approved,
            'approved_by_user_id' => $user->getKey(),
            'approved_at' => now(),
            'impact_json' => $this->preview($proposal),
        ])->save();

        $this->auditLogService->write('master_data_merge_proposal_approved', $proposal, $user, $old, $proposal->getChanges(), ['approval_note' => $note], $proposal->company_id);

        return ['proposal' => $proposal->refresh()];
    }

    /**
     * @return array{proposal: MasterDataMergeProposal}
     */
    public function reject(MasterDataMergeProposal $proposal, User $user, string $reason): array
    {
        $this->requireReason($reason);
        $old = $proposal->getOriginal();

        $proposal->forceFill([
            'status' => MasterDataMergeStatus::Rejected,
            'rejected_by_user_id' => $user->getKey(),
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ])->save();

        $this->auditLogService->write('master_data_merge_proposal_rejected', $proposal, $user, $old, $proposal->getChanges(), [], $proposal->company_id);

        return ['proposal' => $proposal->refresh()];
    }

    private function validatePair(Product $source, Product $target, string $reason): void
    {
        $this->requireReason($reason);

        if ($source->company_id !== $target->company_id) {
            throw new InvalidArgumentException('Product merge source and target must belong to the same company.');
        }

        if ($source->is($target)) {
            throw new InvalidArgumentException('Product merge source and target must differ.');
        }
    }

    private function requireReason(string $reason): void
    {
        if (trim($reason) === '') {
            throw new InvalidArgumentException('Reason is required.');
        }
    }
}
