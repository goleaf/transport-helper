<?php

namespace App\Services\Supply\Analytics;

use App\Models\SupplierConfirmation;
use App\Models\User;

class SupplierConfirmationMismatchReportService
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
        $confirmations = SupplierConfirmation::query()
            ->select(['id', 'company_id', 'supplier_order_id', 'supplier_reference', 'status', 'confirmation_date', 'ready_date', 'expected_arrival_date', 'discrepancies_json', 'created_at'])
            ->when($normalized['company_id'], fn ($query, int $companyId) => $query->where('company_id', $companyId))
            ->whereBetween('created_at', [$createdFrom, $createdTo])
            ->with([
                'supplierOrder:id,supplier_id,order_number',
                'supplierOrder.supplier:id,name',
                'items:id,supplier_confirmation_id,product_id,ordered_quantity,confirmed_quantity,discrepancy_type,status',
                'items.product:id,sku,name',
            ])
            ->latest('id')
            ->limit(500)
            ->get();

        $items = $confirmations->flatMap->items;
        $mismatches = $items->filter(fn ($item): bool => (float) $item->ordered_quantity !== (float) $item->confirmed_quantity || $item->discrepancy_type !== null);

        return [
            'type' => 'supplier_confirmation_mismatches',
            'title' => 'Supplier Confirmation Mismatches',
            'description' => 'Quantity, date and SKU mismatch reporting for supplier confirmations.',
            'filters' => $normalized,
            'summary' => [
                'total_confirmations' => $confirmations->count(),
                'quantity_mismatch_count' => $mismatches->count(),
                'date_mismatch_count' => $confirmations->filter(fn (SupplierConfirmation $confirmation): bool => $this->status($confirmation->status) === 'date_mismatch')->count(),
                'needs_review_count' => $confirmations->filter(fn (SupplierConfirmation $confirmation): bool => $this->status($confirmation->status) === 'needs_review')->count(),
                'unknown_sku_count' => $mismatches->filter(fn ($item): bool => $item->product_id === null)->count(),
            ],
            'by_supplier' => $confirmations
                ->groupBy(fn (SupplierConfirmation $confirmation): string => $confirmation->supplierOrder?->supplier?->name ?? 'Unknown supplier')
                ->map(fn ($group, string $supplier): array => ['supplier' => $supplier, 'mismatch_count' => $group->flatMap->items->filter(fn ($item): bool => (float) $item->ordered_quantity !== (float) $item->confirmed_quantity)->count()])
                ->values()
                ->all(),
            'rows' => $confirmations->map(fn (SupplierConfirmation $confirmation): array => [
                'confirmation_id' => $confirmation->id,
                'supplier' => $confirmation->supplierOrder?->supplier?->name,
                'order_number' => $confirmation->supplierOrder?->order_number,
                'status' => $this->status($confirmation->status),
                'mismatch_count' => $confirmation->items->filter(fn ($item): bool => (float) $item->ordered_quantity !== (float) $item->confirmed_quantity)->count(),
            ])->values()->all(),
            'warnings' => array_merge($normalized['warnings'], $confirmations->isEmpty() ? ['No supplier confirmations found for the selected period.'] : []),
            'definitions' => app(KpiDefinitionService::class)->definitions(),
        ];
    }

    private function status(mixed $status): string
    {
        return $status instanceof \BackedEnum ? $status->value : (string) $status;
    }
}
