<?php

namespace App\Services\Supply\OrderProposals;

use App\Enums\OrderProposalItemStatus;
use App\Enums\OrderProposalStatus;
use App\Models\OrderProposal;
use App\Models\OrderProposalItem;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\Supply\OrderProposals\Concerns\FormatsProposalValues;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class OrderProposalDecisionService
{
    use FormatsProposalValues;

    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function approveItem(OrderProposalItem $item, User $user, array $options = []): array
    {
        return DB::transaction(function () use ($item, $user, $options): array {
            $item->refresh();
            $item->loadMissing(['orderProposal', 'product:id,sku,name']);

            $this->assertItemCanBeChanged($item);

            if ($this->statusValue($item->status) === OrderProposalItemStatus::Rejected->value) {
                throw ValidationException::withMessages([
                    'item' => 'Rejected proposal items cannot be approved.',
                ]);
            }

            if ($item->requires_human_review && empty($options['review_note']) && empty($options['confirmed_review'])) {
                throw ValidationException::withMessages([
                    'review_note' => 'A review note or explicit review confirmation is required.',
                ]);
            }

            $oldValues = $this->itemAuditValues($item);
            $approvedQuantity = $this->quantityForApproval($item, (bool) ($options['force_reapprove'] ?? false));

            $item->forceFill([
                'approved_quantity' => $approvedQuantity,
                'status' => OrderProposalItemStatus::Approved,
            ])->save();

            $item->refresh();

            $this->auditLogService->logDecision('order_quantity_approved', $item, $user, [
                'proposal_id' => $item->order_proposal_id,
                'item_id' => $item->id,
                'product_id' => $item->product_id,
                'sku' => $item->product?->sku,
                'recommended_quantity' => $this->nullableFloat($item->recommended_quantity),
                'approved_quantity' => $this->nullableFloat($item->approved_quantity),
                'requires_human_review' => $item->requires_human_review,
                'review_note' => $options['review_note'] ?? null,
                'confirmed_review' => (bool) ($options['confirmed_review'] ?? false),
                'old_values' => $oldValues,
                'new_values' => $this->itemAuditValues($item),
            ]);

            return [
                'item' => $item,
                'status' => $this->statusValue($item->status),
                'message' => 'Proposal item approved.',
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public function adjustItem(OrderProposalItem $item, array $validated, User $user): array
    {
        return DB::transaction(function () use ($item, $validated, $user): array {
            $item->refresh();
            $item->loadMissing(['orderProposal', 'product:id,sku,name']);

            $this->assertItemCanBeChanged($item);
            $validated = $this->validateAdjustment($validated);
            $oldValues = $this->itemAuditValues($item);

            $item->forceFill([
                'user_adjusted_quantity' => $validated['quantity'],
                'approved_quantity' => $validated['quantity'],
                'adjustment_reason' => $validated['reason'],
                'status' => OrderProposalItemStatus::Adjusted,
            ])->save();

            $item->refresh();

            $this->auditLogService->logDecision('order_quantity_adjusted', $item, $user, [
                'proposal_id' => $item->order_proposal_id,
                'item_id' => $item->id,
                'product_id' => $item->product_id,
                'sku' => $item->product?->sku,
                'old_recommended_quantity' => $this->nullableFloat($oldValues['recommended_quantity'] ?? null),
                'old_approved_quantity' => $this->nullableFloat($oldValues['approved_quantity'] ?? null),
                'old_user_adjusted_quantity' => $this->nullableFloat($oldValues['user_adjusted_quantity'] ?? null),
                'new_quantity' => $this->nullableFloat($validated['quantity']),
                'reason' => $validated['reason'],
                'old_values' => $oldValues,
                'new_values' => $this->itemAuditValues($item),
            ]);

            return [
                'item' => $item,
                'status' => $this->statusValue($item->status),
                'message' => 'Proposal item adjusted.',
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public function rejectItem(OrderProposalItem $item, array $validated, User $user): array
    {
        return DB::transaction(function () use ($item, $validated, $user): array {
            $item->refresh();
            $item->loadMissing(['orderProposal', 'product:id,sku,name']);

            $this->assertItemCanBeChanged($item);
            $validated = $this->validateRejection($validated);
            $oldValues = $this->itemAuditValues($item);
            $previousStatus = $this->statusValue($item->status);

            $item->forceFill([
                'approved_quantity' => null,
                'adjustment_reason' => $validated['reason'],
                'status' => OrderProposalItemStatus::Rejected,
            ])->save();

            $item->refresh();

            $this->auditLogService->logDecision('order_quantity_rejected', $item, $user, [
                'proposal_id' => $item->order_proposal_id,
                'item_id' => $item->id,
                'product_id' => $item->product_id,
                'sku' => $item->product?->sku,
                'previous_status' => $previousStatus,
                'reason' => $validated['reason'],
                'old_values' => $oldValues,
                'new_values' => $this->itemAuditValues($item),
            ]);

            return [
                'item' => $item,
                'status' => $this->statusValue($item->status),
                'message' => 'Proposal item rejected.',
            ];
        });
    }

    public function hasUnresolvedItems(OrderProposal $proposal): bool
    {
        return $proposal->items()
            ->whereIn('status', [
                OrderProposalItemStatus::Draft->value,
                OrderProposalItemStatus::NeedsReview->value,
            ])
            ->exists();
    }

    private function assertItemCanBeChanged(OrderProposalItem $item): void
    {
        if ($this->statusValue($item->orderProposal?->status) === OrderProposalStatus::ConvertedToSupplierOrder->value) {
            throw ValidationException::withMessages([
                'proposal' => 'Converted proposals cannot be changed.',
            ]);
        }
    }

    private function quantityForApproval(OrderProposalItem $item, bool $forceReapprove): float
    {
        if (! $forceReapprove
            && $this->statusValue($item->status) === OrderProposalItemStatus::Adjusted->value
            && $item->user_adjusted_quantity !== null) {
            return (float) $item->user_adjusted_quantity;
        }

        return (float) ($item->recommended_quantity ?? 0);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{quantity:int|float|string, reason:string}
     */
    private function validateAdjustment(array $data): array
    {
        return Validator::make($data, [
            'quantity' => ['required', 'numeric', 'min:0'],
            'reason' => ['required', 'string', 'min:3', 'max:5000'],
        ])->validate();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{reason:string}
     */
    private function validateRejection(array $data): array
    {
        return Validator::make($data, [
            'reason' => ['required', 'string', 'min:3', 'max:5000'],
        ])->validate();
    }

    /**
     * @return array<string, mixed>
     */
    private function itemAuditValues(OrderProposalItem $item): array
    {
        return [
            'status' => $this->statusValue($item->status),
            'recommended_quantity' => $this->nullableFloat($item->recommended_quantity),
            'approved_quantity' => $this->nullableFloat($item->approved_quantity),
            'user_adjusted_quantity' => $this->nullableFloat($item->user_adjusted_quantity),
            'adjustment_reason' => $item->adjustment_reason,
            'requires_human_review' => $item->requires_human_review,
        ];
    }
}
