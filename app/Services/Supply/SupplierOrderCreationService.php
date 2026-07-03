<?php

namespace App\Services\Supply;

use App\Enums\OrderProposalItemStatus;
use App\Enums\OrderProposalStatus;
use App\Enums\SupplierOrderStatus;
use App\Models\AuditLog;
use App\Models\OrderProposal;
use App\Models\SupplierOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupplierOrderCreationService
{
    public function __construct(
        private readonly OrderProposalDecisionService $decisionService,
        private readonly LogisticsRecordService $logisticsRecordService,
    ) {}

    public function createFromApprovedProposal(OrderProposal $proposal, User $user): SupplierOrder
    {
        return DB::transaction(function () use ($proposal, $user): SupplierOrder {
            $proposal->refresh();

            if ($proposal->status !== OrderProposalStatus::Approved) {
                throw ValidationException::withMessages([
                    'proposal' => 'Only approved proposals can create supplier orders.',
                ]);
            }

            if ($this->decisionService->hasUnresolvedItems($proposal)) {
                throw ValidationException::withMessages([
                    'proposal' => 'Proposal has unresolved items and cannot create a supplier order.',
                ]);
            }

            $existingSupplierOrder = $proposal->supplierOrder()
                ->select(['id'])
                ->first();

            if ($existingSupplierOrder instanceof SupplierOrder) {
                return SupplierOrder::query()
                    ->with(['items', 'logisticsRecords'])
                    ->findOrFail($existingSupplierOrder->id);
            }

            $supplierOrder = SupplierOrder::query()->create([
                'company_id' => $proposal->company_id,
                'supplier_id' => $proposal->supplier_id,
                'order_proposal_id' => $proposal->id,
                'order_number' => $this->orderNumber($proposal),
                'status' => SupplierOrderStatus::Draft,
                'order_date' => now()->toDateString(),
                'approved_by_user_id' => $user->id,
                'approved_at' => now(),
            ]);

            $proposal->items()
                ->select(['id', 'product_id', 'approved_quantity', 'status'])
                ->whereIn('status', [
                    OrderProposalItemStatus::Approved->value,
                    OrderProposalItemStatus::Adjusted->value,
                ])
                ->orderBy('id')
                ->get()
                ->each(function ($item) use ($supplierOrder): void {
                    $supplierOrder->items()->create([
                        'product_id' => $item->product_id,
                        'ordered_quantity' => $item->approved_quantity,
                        'status' => 'draft',
                    ]);
                });

            $this->logisticsRecordService->ensureForSupplierOrder($supplierOrder, $user);

            $oldProposalValues = $proposal->only(['status']);

            $proposal->forceFill([
                'status' => OrderProposalStatus::ConvertedToSupplierOrder,
            ])->save();

            $this->writeAuditLog(
                eventType: 'supplier_order.created_from_proposal',
                user: $user,
                auditable: $supplierOrder,
                companyId: $proposal->company_id,
                oldValues: [],
                newValues: [
                    'supplier_order_id' => $supplierOrder->id,
                    'order_number' => $supplierOrder->order_number,
                    'status' => $supplierOrder->status,
                ],
                metadata: [
                    'order_proposal_id' => $proposal->id,
                ],
            );

            $this->writeAuditLog(
                eventType: 'order_proposal.converted_to_supplier_order',
                user: $user,
                auditable: $proposal,
                companyId: $proposal->company_id,
                oldValues: $oldProposalValues,
                newValues: [
                    'status' => $proposal->status,
                    'supplier_order_id' => $supplierOrder->id,
                ],
                metadata: [
                    'supplier_order_number' => $supplierOrder->order_number,
                ],
            );

            return $supplierOrder->load(['items', 'logisticsRecords']);
        });
    }

    private function orderNumber(OrderProposal $proposal): string
    {
        return sprintf('PO-%s-%s', $proposal->id, now()->format('YmdHis'));
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     * @param  array<string, mixed>  $metadata
     */
    private function writeAuditLog(
        string $eventType,
        User $user,
        OrderProposal|SupplierOrder $auditable,
        int $companyId,
        array $oldValues,
        array $newValues,
        array $metadata = [],
    ): void {
        AuditLog::query()->create([
            'company_id' => $companyId,
            'user_id' => $user->id,
            'event_type' => $eventType,
            'auditable_type' => $auditable::class,
            'auditable_id' => $auditable->id,
            'old_values_json' => $oldValues,
            'new_values_json' => $newValues,
            'metadata_json' => $metadata,
            'created_at' => now(),
        ]);
    }
}
