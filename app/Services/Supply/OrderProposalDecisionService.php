<?php

namespace App\Services\Supply;

use App\Enums\OrderProposalItemStatus;
use App\Enums\OrderProposalStatus;
use App\Models\OrderProposal;
use App\Models\OrderProposalItem;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Support\Facades\DB;

class OrderProposalDecisionService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    public function approveItem(OrderProposalItem $item, User $user): OrderProposalItem
    {
        return DB::transaction(function () use ($item, $user): OrderProposalItem {
            $item->refresh();

            $oldValues = $this->itemAuditValues($item);
            $approvedQuantity = $item->user_adjusted_quantity ?? $item->recommended_quantity ?? 0;

            $item->forceFill([
                'approved_quantity' => $approvedQuantity,
                'status' => OrderProposalItemStatus::Approved,
                'requires_human_review' => false,
            ])->save();

            $this->auditLogService->write(
                eventType: 'order_proposal_item.approved',
                user: $user,
                auditable: $item,
                companyId: $this->companyIdForItem($item),
                oldValues: $oldValues,
                newValues: $this->itemAuditValues($item),
            );

            return $item;
        });
    }

    /**
     * @param  array{quantity:numeric-string|int|float,reason:string}  $data
     */
    public function adjustItem(OrderProposalItem $item, User $user, array $data): OrderProposalItem
    {
        return DB::transaction(function () use ($item, $user, $data): OrderProposalItem {
            $item->refresh();

            $oldValues = $this->itemAuditValues($item);

            $item->forceFill([
                'user_adjusted_quantity' => $data['quantity'],
                'approved_quantity' => $data['quantity'],
                'adjustment_reason' => $data['reason'],
                'status' => OrderProposalItemStatus::Adjusted,
                'requires_human_review' => false,
            ])->save();

            $this->auditLogService->logOrderQuantityAdjusted(
                item: $item,
                user: $user,
                companyId: $this->companyIdForItem($item),
                oldValues: $oldValues,
                newValues: $this->itemAuditValues($item),
            );

            return $item;
        });
    }

    public function rejectItem(OrderProposalItem $item, User $user): OrderProposalItem
    {
        return DB::transaction(function () use ($item, $user): OrderProposalItem {
            $item->refresh();

            $oldValues = $this->itemAuditValues($item);

            $item->forceFill([
                'approved_quantity' => null,
                'status' => OrderProposalItemStatus::Rejected,
                'requires_human_review' => false,
            ])->save();

            $this->auditLogService->write(
                eventType: 'order_proposal_item.rejected',
                user: $user,
                auditable: $item,
                companyId: $this->companyIdForItem($item),
                oldValues: $oldValues,
                newValues: $this->itemAuditValues($item),
            );

            return $item;
        });
    }

    public function approveProposal(OrderProposal $proposal, User $user): OrderProposal
    {
        return DB::transaction(function () use ($proposal, $user): OrderProposal {
            $proposal->refresh();

            $oldValues = $this->proposalAuditValues($proposal);

            $proposal->forceFill([
                'status' => OrderProposalStatus::Approved,
                'approved_by_user_id' => $user->id,
                'approved_at' => now(),
            ])->save();

            $this->auditLogService->write(
                eventType: 'order_proposal.approved',
                user: $user,
                auditable: $proposal,
                companyId: $proposal->company_id,
                oldValues: $oldValues,
                newValues: $this->proposalAuditValues($proposal),
            );

            return $proposal;
        });
    }

    public function hasUnresolvedItems(OrderProposal $proposal): bool
    {
        return $proposal->items()
            ->where(function ($query): void {
                $query
                    ->whereNotIn('status', [
                        OrderProposalItemStatus::Approved->value,
                        OrderProposalItemStatus::Adjusted->value,
                    ])
                    ->orWhere('requires_human_review', true)
                    ->orWhereNull('approved_quantity');
            })
            ->exists();
    }

    /**
     * @return array<string, mixed>
     */
    private function itemAuditValues(OrderProposalItem $item): array
    {
        return $item->only([
            'status',
            'recommended_quantity',
            'approved_quantity',
            'user_adjusted_quantity',
            'adjustment_reason',
            'requires_human_review',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function proposalAuditValues(OrderProposal $proposal): array
    {
        return $proposal->only([
            'status',
            'approved_by_user_id',
            'approved_at',
        ]);
    }

    private function companyIdForItem(OrderProposalItem $item): int
    {
        return (int) $item->orderProposal()
            ->select(['id', 'company_id'])
            ->value('company_id');
    }
}
