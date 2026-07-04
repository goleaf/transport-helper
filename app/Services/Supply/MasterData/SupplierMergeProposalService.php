<?php

namespace App\Services\Supply\MasterData;

use App\Enums\MasterDataMergeStatus;
use App\Models\MasterDataMergeProposal;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use InvalidArgumentException;

class SupplierMergeProposalService
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    /**
     * @return array{proposal: MasterDataMergeProposal}
     */
    public function createProposal(Supplier $source, Supplier $target, User $user, string $reason): array
    {
        $this->validatePair($source, $target, $reason);

        $proposal = MasterDataMergeProposal::query()->create([
            'company_id' => $source->company_id,
            'merge_type' => 'supplier',
            'source_model_type' => Supplier::class,
            'source_model_id' => $source->getKey(),
            'target_model_type' => Supplier::class,
            'target_model_id' => $target->getKey(),
            'status' => MasterDataMergeStatus::Draft,
            'reason' => $reason,
            'impact_json' => [],
            'proposed_by_user_id' => $user->getKey(),
        ]);
        $proposal->forceFill(['impact_json' => $this->preview($proposal)])->save();

        $this->auditLogService->write('master_data_merge_proposal_created', $proposal, $user, null, [
            'merge_type' => 'supplier',
            'source_supplier_id' => $source->id,
            'target_supplier_id' => $target->id,
        ], [], $proposal->company_id);

        return ['proposal' => $proposal->refresh()];
    }

    /**
     * @return array<string,mixed>
     */
    public function preview(MasterDataMergeProposal $proposal): array
    {
        $source = Supplier::query()->findOrFail($proposal->source_model_id);
        $target = Supplier::query()->findOrFail($proposal->target_model_id);

        return [
            'source' => ['id' => $source->id, 'code' => $source->code, 'name' => $source->name],
            'target' => ['id' => $target->id, 'code' => $target->code, 'name' => $target->name],
            'affected_tables' => [
                'supplier_contacts' => $source->contacts()->count(),
                'supplier_product_rules' => $source->productRules()->count(),
                'inbound_orders' => $source->inboundOrders()->count(),
                'calculation_runs' => $source->calculationRuns()->count(),
                'order_proposals' => $source->orderProposals()->count(),
                'supplier_orders' => $source->supplierOrders()->count(),
                'logistics_records' => $source->logisticsRecords()->count(),
                'form_templates' => $source->formTemplates()->count(),
                'aliases' => $source->aliases()->count(),
            ],
            'aliases_to_create' => array_values(array_filter([$source->name, $source->code])),
            'field_differences' => [
                'name' => [$source->name, $target->name],
                'code' => [$source->code, $target->code],
                'type' => [$source->type?->value ?? $source->type, $target->type?->value ?? $target->type],
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

    private function validatePair(Supplier $source, Supplier $target, string $reason): void
    {
        $this->requireReason($reason);

        if ($source->company_id !== $target->company_id) {
            throw new InvalidArgumentException('Supplier merge source and target must belong to the same company.');
        }

        if ($source->is($target)) {
            throw new InvalidArgumentException('Supplier merge source and target must differ.');
        }
    }

    private function requireReason(string $reason): void
    {
        if (trim($reason) === '') {
            throw new InvalidArgumentException('Reason is required.');
        }
    }
}
