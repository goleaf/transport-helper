<?php

namespace App\Services\Supply\Analytics;

use App\Models\SupplierOrderItem;
use App\Models\User;

class ReceivingAccuracyReportService
{
    public function __construct(private readonly AnalyticsFilterService $filters) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function report(array $filters = [], ?User $user = null): array
    {
        $normalized = $this->filters->normalize($filters);
        $items = SupplierOrderItem::query()
            ->select(['id', 'supplier_order_id', 'product_id', 'ordered_quantity', 'confirmed_quantity', 'received_quantity', 'damaged_quantity', 'created_at'])
            ->whereNotNull('received_quantity')
            ->with([
                'supplierOrder:id,supplier_id,order_number',
                'supplierOrder.supplier:id,name',
                'product:id,sku,name',
            ])
            ->latest('id')
            ->limit(500)
            ->get();

        $matched = $items->filter(fn (SupplierOrderItem $item): bool => (float) $item->received_quantity === (float) ($item->confirmed_quantity ?? $item->ordered_quantity));
        $mismatches = $items->count() - $matched->count();

        return [
            'type' => 'receiving_accuracy',
            'title' => 'Receiving Accuracy',
            'description' => 'Received quantity, mismatch and damage reporting.',
            'filters' => $normalized,
            'summary' => [
                'received_orders_count' => $items->pluck('supplier_order_id')->unique()->count(),
                'fully_matched_receipts' => $matched->count(),
                'receiving_mismatches' => $mismatches,
                'damaged_quantity_count' => (float) $items->sum('damaged_quantity'),
                'received_less_count' => $items->filter(fn (SupplierOrderItem $item): bool => (float) $item->received_quantity < (float) ($item->confirmed_quantity ?? $item->ordered_quantity))->count(),
                'received_more_count' => $items->filter(fn (SupplierOrderItem $item): bool => (float) $item->received_quantity > (float) ($item->confirmed_quantity ?? $item->ordered_quantity))->count(),
                'receiving_match_rate' => $this->percentage($matched->count(), $items->count()),
                'average_receiving_delay' => 0.0,
            ],
            'rows' => $items->map(fn (SupplierOrderItem $item): array => [
                'supplier_order_item_id' => $item->id,
                'supplier' => $item->supplierOrder?->supplier?->name,
                'product' => $item->product?->sku,
                'ordered_quantity' => (float) $item->ordered_quantity,
                'confirmed_quantity' => (float) ($item->confirmed_quantity ?? $item->ordered_quantity),
                'received_quantity' => (float) $item->received_quantity,
                'damaged_quantity' => (float) $item->damaged_quantity,
            ])->values()->all(),
            'warnings' => array_merge($normalized['warnings'], $items->isEmpty() ? ['No received order items found for the selected period.'] : []),
            'definitions' => app(KpiDefinitionService::class)->definitions(),
        ];
    }

    private function percentage(int|float $value, int|float $total): float
    {
        return $total > 0 ? round(($value / $total) * 100, 2) : 0.0;
    }
}
