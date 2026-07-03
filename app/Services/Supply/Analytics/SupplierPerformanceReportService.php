<?php

namespace App\Services\Supply\Analytics;

use App\Models\Supplier;
use App\Models\User;

class SupplierPerformanceReportService
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
        $suppliers = Supplier::query()
            ->select(['id', 'company_id', 'name', 'default_lead_time_days'])
            ->when($normalized['company_id'], fn ($query, int $companyId) => $query->where('company_id', $companyId))
            ->when($normalized['supplier_id'], fn ($query, int $supplierId) => $query->whereKey($supplierId))
            ->with([
                'supplierOrders' => fn ($query) => $query
                    ->select(['id', 'company_id', 'supplier_id', 'status', 'sent_at', 'order_date', 'created_at'])
                    ->whereBetween('created_at', [$createdFrom, $createdTo]),
                'supplierOrders.confirmations' => fn ($query) => $query
                    ->select(['id', 'company_id', 'supplier_order_id', 'confirmation_date', 'ready_date', 'expected_arrival_date', 'status', 'created_at']),
                'supplierOrders.confirmations.items' => fn ($query) => $query
                    ->select(['id', 'supplier_confirmation_id', 'ordered_quantity', 'confirmed_quantity', 'discrepancy_quantity', 'status']),
                'logisticsRecords' => fn ($query) => $query
                    ->select(['id', 'supplier_id', 'status', 'ready_date', 'delivery_date', 'actual_received_date'])
                    ->whereBetween('created_at', [$normalized['date_from'], $normalized['date_to']]),
            ])
            ->orderBy('name')
            ->limit(200)
            ->get();

        $rows = $suppliers->map(function (Supplier $supplier): array {
            $orders = $supplier->supplierOrders;
            $confirmations = $orders->flatMap->confirmations;
            $items = $confirmations->flatMap->items;
            $matchedItems = $items->filter(fn ($item): bool => (float) $item->ordered_quantity === (float) $item->confirmed_quantity);
            $delays = $supplier->logisticsRecords->filter(fn ($record): bool => $this->status($record->status) === 'delayed')->count();
            $sentOrders = $orders->filter(fn ($order): bool => $order->sent_at !== null);
            $daysToConfirmation = $confirmations
                ->filter(fn ($confirmation): bool => $confirmation->confirmation_date !== null)
                ->map(fn ($confirmation): int => max(0, $confirmation->created_at?->diffInDays($confirmation->confirmation_date) ?? 0));

            $mismatchCount = $items->count() - $matchedItems->count();
            $confirmationRate = $this->percentage($confirmations->count(), max($sentOrders->count(), $orders->count()));
            $quantityMatchRate = $this->percentage($matchedItems->count(), $items->count());

            return [
                'supplier_id' => $supplier->id,
                'supplier' => $supplier->name,
                'total_supplier_orders' => $orders->count(),
                'sent_orders' => $sentOrders->count(),
                'confirmed_orders' => $confirmations->pluck('supplier_order_id')->unique()->count(),
                'confirmation_rate' => $confirmationRate,
                'average_days_to_confirmation' => round((float) $daysToConfirmation->avg(), 2),
                'average_ready_date_lead_time' => (float) $supplier->default_lead_time_days,
                'quantity_match_rate' => $quantityMatchRate,
                'mismatch_count' => $mismatchCount,
                'delay_count' => $delays,
                'delayed_ready_date_count' => $delays,
                'delayed_arrival_count' => $delays,
                'open_issues_count' => $mismatchCount + $delays,
                'risk_level' => $this->riskLevel($quantityMatchRate, $delays, $mismatchCount),
            ];
        })->values()->all();

        return [
            'type' => 'supplier_performance',
            'title' => 'Supplier Performance',
            'description' => 'Supplier confirmation, quantity match and delay performance.',
            'filters' => $normalized,
            'summary' => [
                'total_supplier_orders' => array_sum(array_column($rows, 'total_supplier_orders')),
                'sent_orders' => array_sum(array_column($rows, 'sent_orders')),
                'confirmed_orders' => array_sum(array_column($rows, 'confirmed_orders')),
                'confirmation_rate' => $this->percentage(array_sum(array_column($rows, 'confirmed_orders')), max(array_sum(array_column($rows, 'sent_orders')), array_sum(array_column($rows, 'total_supplier_orders')))),
                'quantity_match_rate' => round((float) collect($rows)->avg('quantity_match_rate'), 2),
                'mismatch_count' => array_sum(array_column($rows, 'mismatch_count')),
                'delay_count' => array_sum(array_column($rows, 'delay_count')),
            ],
            'rows' => $rows,
            'warnings' => array_merge($normalized['warnings'], $rows === [] ? ['No supplier performance data found for the selected period.'] : []),
            'definitions' => app(KpiDefinitionService::class)->definitions(),
        ];
    }

    private function riskLevel(float $quantityMatchRate, int $delays, int $mismatchCount): string
    {
        if ($delays > 0 || $quantityMatchRate < 80 || $mismatchCount > 3) {
            return 'high';
        }

        if ($quantityMatchRate < 95 || $mismatchCount > 0) {
            return 'medium';
        }

        return 'low';
    }

    private function percentage(int|float $value, int|float $total): float
    {
        if ($total <= 0) {
            return 0.0;
        }

        return round(($value / $total) * 100, 2);
    }

    private function status(mixed $status): string
    {
        return $status instanceof \BackedEnum ? $status->value : (string) $status;
    }
}
