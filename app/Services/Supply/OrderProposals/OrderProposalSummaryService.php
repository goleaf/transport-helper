<?php

namespace App\Services\Supply\OrderProposals;

use App\Enums\OrderProposalItemStatus;
use App\Enums\OrderProposalStatus;
use App\Models\OrderProposal;
use Illuminate\Support\Collection;

class OrderProposalSummaryService
{
    /**
     * @return array<string, mixed>
     */
    public function summarize(OrderProposal $proposal): array
    {
        $items = $this->itemsForSummary($proposal);

        $draftCount = $this->countByStatus($items, OrderProposalItemStatus::Draft);
        $needsReviewCount = $this->countByStatus($items, OrderProposalItemStatus::NeedsReview);
        $approvedCount = $this->countByStatus($items, OrderProposalItemStatus::Approved);
        $adjustedCount = $this->countByStatus($items, OrderProposalItemStatus::Adjusted);
        $rejectedCount = $this->countByStatus($items, OrderProposalItemStatus::Rejected);
        $resolvedCount = $approvedCount + $adjustedCount + $rejectedCount;
        $unresolvedCount = $draftCount + $needsReviewCount;

        $orderableItems = $items->filter(function ($item): bool {
            return in_array($this->statusValue($item->status), [
                OrderProposalItemStatus::Approved->value,
                OrderProposalItemStatus::Adjusted->value,
            ], true) && (float) $item->approved_quantity > 0;
        });

        $blockingReasons = [];

        if ($unresolvedCount > 0) {
            $blockingReasons[] = 'unresolved_items_exist';
        }

        if ($orderableItems->isEmpty()) {
            $blockingReasons[] = 'no_orderable_lines';
        }

        if ($this->statusValue($proposal->status) === OrderProposalStatus::ConvertedToSupplierOrder->value) {
            $blockingReasons[] = 'proposal_already_converted';
        }

        return [
            'total_lines' => $items->count(),
            'draft_count' => $draftCount,
            'needs_review_count' => $needsReviewCount,
            'approved_count' => $approvedCount,
            'adjusted_count' => $adjustedCount,
            'rejected_count' => $rejectedCount,
            'resolved_count' => $resolvedCount,
            'unresolved_count' => $unresolvedCount,
            'orderable_count' => $orderableItems->count(),
            'total_recommended_quantity' => round((float) $items->sum(fn ($item): float => (float) $item->recommended_quantity), 4),
            'total_approved_quantity' => round((float) $orderableItems->sum(fn ($item): float => (float) $item->approved_quantity), 4),
            'can_approve' => $unresolvedCount === 0
                && $orderableItems->isNotEmpty()
                && $this->statusValue($proposal->status) !== OrderProposalStatus::ConvertedToSupplierOrder->value,
            'can_convert' => $this->statusValue($proposal->status) === OrderProposalStatus::Approved->value
                && $orderableItems->isNotEmpty(),
            'blocking_reasons' => $blockingReasons,
        ];
    }

    /**
     * @return Collection<int, object>
     */
    private function itemsForSummary(OrderProposal $proposal): Collection
    {
        if ($proposal->relationLoaded('items')) {
            return $proposal->items;
        }

        return $proposal->items()
            ->select([
                'id',
                'order_proposal_id',
                'status',
                'recommended_quantity',
                'approved_quantity',
            ])
            ->get();
    }

    /**
     * @param  Collection<int, object>  $items
     */
    private function countByStatus(Collection $items, OrderProposalItemStatus $status): int
    {
        return $items
            ->filter(fn ($item): bool => $this->statusValue($item->status) === $status->value)
            ->count();
    }

    private function statusValue(mixed $status): string
    {
        return $status instanceof \BackedEnum ? $status->value : (string) $status;
    }
}
