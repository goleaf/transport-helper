<?php

namespace App\Services\Supply\Analytics;

use App\Models\OrderProposalItem;
use App\Models\User;

class OrderProposalQualityReportService
{
    public function __construct(private readonly AnalyticsFilterService $filters) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function report(array $filters = [], ?User $user = null): array
    {
        $normalized = $this->filters->normalize($filters);
        $createdFrom = $normalized['date_from'].' 00:00:00';
        $createdTo = $normalized['date_to'].' 23:59:59';
        $items = OrderProposalItem::query()
            ->select(['id', 'order_proposal_id', 'product_id', 'status', 'recommended_quantity', 'approved_quantity', 'user_adjusted_quantity', 'adjustment_reason', 'requires_human_review', 'warnings_json', 'created_at'])
            ->whereBetween('created_at', [$createdFrom, $createdTo])
            ->latest('id')
            ->limit(1000)
            ->get();

        $adjusted = $items->filter(fn (OrderProposalItem $item): bool => $this->status($item->status) === 'adjusted');
        $reasons = $adjusted
            ->groupBy(fn (OrderProposalItem $item): string => $item->adjustment_reason ?: 'No reason')
            ->map(fn ($group, string $reason): array => ['reason' => $reason, 'count' => $group->count()])
            ->sortByDesc('count')
            ->values()
            ->all();

        return [
            'type' => 'order_proposal_quality',
            'title' => 'Order Proposal Quality',
            'description' => 'Proposal decisions, adjustment rates and review signals.',
            'filters' => $normalized,
            'summary' => [
                'total_proposal_items' => $items->count(),
                'approved_count' => $items->filter(fn (OrderProposalItem $item): bool => $this->status($item->status) === 'approved')->count(),
                'adjusted_count' => $adjusted->count(),
                'rejected_count' => $items->filter(fn (OrderProposalItem $item): bool => $this->status($item->status) === 'rejected')->count(),
                'needs_review_count' => $items->filter(fn (OrderProposalItem $item): bool => $this->status($item->status) === 'needs_review' || $item->requires_human_review)->count(),
                'adjustment_rate' => $this->percentage($adjusted->count(), $items->count()),
                'average_adjustment_percentage' => $this->averageAdjustmentPercentage($adjusted),
                'items_with_missing_data_warnings' => $items->filter(fn (OrderProposalItem $item): bool => ($item->warnings_json ?? []) !== [])->count(),
            ],
            'top_adjustment_reasons' => $reasons,
            'rows' => $items->map(fn (OrderProposalItem $item): array => [
                'proposal_item_id' => $item->id,
                'status' => $this->status($item->status),
                'recommended_quantity' => (float) $item->recommended_quantity,
                'approved_quantity' => $item->approved_quantity === null ? null : (float) $item->approved_quantity,
                'user_adjusted_quantity' => $item->user_adjusted_quantity === null ? null : (float) $item->user_adjusted_quantity,
                'adjustment_reason' => $item->adjustment_reason,
                'requires_human_review' => (bool) $item->requires_human_review,
            ])->values()->all(),
            'warnings' => array_merge($normalized['warnings'], $items->isEmpty() ? ['No proposal items found for the selected period.'] : []),
            'definitions' => app(KpiDefinitionService::class)->definitions(),
        ];
    }

    private function averageAdjustmentPercentage($items): float
    {
        $values = $items->map(function (OrderProposalItem $item): ?float {
            $recommended = (float) $item->recommended_quantity;
            $adjusted = (float) ($item->user_adjusted_quantity ?? $item->approved_quantity);

            return $recommended > 0 ? abs($adjusted - $recommended) / $recommended * 100 : null;
        })->filter();

        return round((float) $values->avg(), 2);
    }

    private function percentage(int|float $value, int|float $total): float
    {
        return $total > 0 ? round(($value / $total) * 100, 2) : 0.0;
    }

    private function status(mixed $status): string
    {
        return $status instanceof \BackedEnum ? $status->value : (string) $status;
    }
}
