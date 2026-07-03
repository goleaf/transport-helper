<?php

namespace App\Services\Supply\OrderProposals;

use App\Enums\LogisticsStatus;
use App\Enums\OrderProposalItemStatus;
use App\Enums\OrderProposalStatus;
use App\Enums\SupplierOrderStatus;
use App\Models\LogisticsRecord;
use App\Models\OrderProposal;
use App\Models\SupplierOrder;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\Supply\OrderProposals\Concerns\FormatsProposalValues;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class SupplierOrderCreationService
{
    use FormatsProposalValues;

    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function createFromApprovedProposal(OrderProposal $proposal, User $user, array $options = []): array
    {
        return DB::transaction(function () use ($proposal, $user, $options): array {
            $proposal->refresh();

            if ($this->statusValue($proposal->status) === OrderProposalStatus::ConvertedToSupplierOrder->value) {
                throw ValidationException::withMessages([
                    'proposal' => 'This proposal has already been converted to a supplier order.',
                ]);
            }

            if ($this->statusValue($proposal->status) !== OrderProposalStatus::Approved->value) {
                throw ValidationException::withMessages([
                    'proposal' => 'Only approved proposals can be converted to supplier orders.',
                ]);
            }

            $existingOrder = $proposal->supplierOrder()
                ->select(['id', 'order_number'])
                ->first();

            if ($existingOrder instanceof SupplierOrder) {
                throw ValidationException::withMessages([
                    'proposal' => "This proposal already has supplier order {$existingOrder->order_number}.",
                ]);
            }

            $orderableItems = $proposal->items()
                ->select(['id', 'order_proposal_id', 'product_id', 'status', 'approved_quantity'])
                ->whereIn('status', [
                    OrderProposalItemStatus::Approved->value,
                    OrderProposalItemStatus::Adjusted->value,
                ])
                ->where('approved_quantity', '>', 0)
                ->orderBy('id')
                ->get();

            if ($orderableItems->isEmpty()) {
                throw ValidationException::withMessages([
                    'proposal' => 'This proposal has no approved or adjusted positive-quantity lines.',
                ]);
            }

            $supplierOrder = SupplierOrder::query()->create([
                'company_id' => $proposal->company_id,
                'supplier_id' => $proposal->supplier_id,
                'order_proposal_id' => $proposal->id,
                'order_number' => $this->generateOrderNumber($proposal),
                'status' => SupplierOrderStatus::Draft,
                'order_date' => now()->toDateString(),
                'notes' => "Created from order proposal #{$proposal->id}",
            ]);

            foreach ($orderableItems as $item) {
                $supplierOrder->items()->create([
                    'product_id' => $item->product_id,
                    'ordered_quantity' => $item->approved_quantity,
                    'status' => 'draft',
                    'notes' => "Created from proposal item #{$item->id}",
                ]);
            }

            $logisticsRecord = $this->createLogisticsRecord($supplierOrder);
            $oldStatus = $this->statusValue($proposal->status);

            $proposal->forceFill([
                'status' => OrderProposalStatus::ConvertedToSupplierOrder,
            ])->save();

            $proposal->refresh();

            $excludedRejectedCount = $proposal->items()
                ->where('status', OrderProposalItemStatus::Rejected->value)
                ->count();
            $excludedZeroQuantityCount = $proposal->items()
                ->whereIn('status', [
                    OrderProposalItemStatus::Approved->value,
                    OrderProposalItemStatus::Adjusted->value,
                ])
                ->where(function ($query): void {
                    $query->whereNull('approved_quantity')->orWhere('approved_quantity', '<=', 0);
                })
                ->count();

            $metadata = [
                'proposal_id' => $proposal->id,
                'supplier_order_id' => $supplierOrder->id,
                'order_number' => $supplierOrder->order_number,
                'items_count' => $orderableItems->count(),
                'excluded_rejected_count' => $excludedRejectedCount,
                'excluded_zero_quantity_count' => $excludedZeroQuantityCount,
                'options' => $options,
            ];

            $this->auditLogService->logDecision('supplier_order_created', $supplierOrder, $user, $metadata);
            $this->auditLogService->logStatusChanged($proposal, $oldStatus, $this->statusValue($proposal->status), $user, $metadata);
            $this->auditLogService->logDecision('order_proposal_converted_to_supplier_order', $proposal, $user, $metadata);

            if ($logisticsRecord instanceof LogisticsRecord) {
                $this->auditLogService->logDecision('logistics_record_created', $logisticsRecord, $user, [
                    'proposal_id' => $proposal->id,
                    'supplier_order_id' => $supplierOrder->id,
                    'status' => $this->statusValue($logisticsRecord->status),
                ]);
            }

            return [
                'supplier_order' => $supplierOrder->load(['items.product', 'logisticsRecords']),
                'items_count' => $orderableItems->count(),
                'logistics_record' => $logisticsRecord,
            ];
        });
    }

    protected function generateOrderNumber(OrderProposal $proposal): string
    {
        $base = sprintf('PO-%s-%s', now()->format('Ymd'), $proposal->getKey());
        $candidate = $base;
        $suffix = 2;

        while (SupplierOrder::query()->where('order_number', $candidate)->exists()) {
            $candidate = "{$base}-{$suffix}";
            $suffix++;
        }

        return $candidate;
    }

    private function createLogisticsRecord(SupplierOrder $supplierOrder): ?LogisticsRecord
    {
        if (! class_exists(LogisticsRecord::class) || ! Schema::hasTable('logistics_records')) {
            return null;
        }

        return LogisticsRecord::query()->create([
            'company_id' => $supplierOrder->company_id,
            'supplier_order_id' => $supplierOrder->id,
            'supplier_id' => $supplierOrder->supplier_id,
            'order_date' => $supplierOrder->order_date,
            'status' => LogisticsStatus::Planned,
            'notes' => "Created from supplier order #{$supplierOrder->id}",
        ]);
    }
}
