<?php

namespace App\Services\Supply\Procurement;

use App\Enums\SupplierOrderStatus;
use App\Models\Company;
use App\Models\ProcurementBudget;
use App\Models\SupplierOrder;
use App\Services\Audit\AuditLogService;

class BudgetAvailabilityService
{
    public function __construct(
        private readonly BudgetService $budgetService,
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * @param  array<string, mixed>  $estimation
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function check(Company $company, array $estimation, array $context = []): array
    {
        $date = (string) ($context['date'] ?? now()->toDateString());
        $currency = (string) ($estimation['currency'] ?? config('supply.procurement.default_currency', 'EUR'));
        $budget = isset($context['budget_id'])
            ? ProcurementBudget::query()->with('lines')->where('company_id', $company->getKey())->find($context['budget_id'])
            : $this->budgetService->activeBudgetForDate($company, $date, $currency);

        if (! $budget instanceof ProcurementBudget) {
            $result = [
                'status' => 'warning',
                'budget_id' => null,
                'allocated_amount' => 0.0,
                'committed_amount' => 0.0,
                'spent_amount' => 0.0,
                'available_amount' => 0.0,
                'estimated_amount' => (float) ($estimation['total'] ?? 0),
                'over_budget_amount' => 0.0,
                'currency' => $currency,
                'warnings' => ['missing_budget'],
                'blocking_reasons' => [],
            ];

            $this->auditLogService->write('procurement_budget_checked', null, null, null, $result, [], $company->getKey());

            return $result;
        }

        $matchingLines = $budget->lines->filter(function ($line) use ($context): bool {
            $productIds = collect($context['product_ids'] ?? [])->map(fn (mixed $id): int => (int) $id)->all();

            if ($line->supplier_id !== null && (int) $line->supplier_id !== (int) ($context['supplier_id'] ?? 0)) {
                return false;
            }

            if ($line->product_id !== null && ! in_array((int) $line->product_id, $productIds, true)) {
                return false;
            }

            if ($line->category !== null && $line->category !== ($context['category'] ?? null)) {
                return false;
            }

            return true;
        });
        $allocated = $matchingLines->isNotEmpty()
            ? (float) $matchingLines->sum(fn ($line): float => (float) $line->amount)
            : (float) $budget->total_amount;
        $committedSpent = $this->committedAndSpent($company, $budget, $context);
        $available = $allocated - $committedSpent['committed'] - $committedSpent['spent'];
        $estimated = (float) ($estimation['total'] ?? 0);
        $overBudget = max(0, $estimated - $available);
        $warnings = $overBudget > 0 ? ['budget_overrun'] : [];
        $blockingReasons = $overBudget > 0 ? ['budget_overrun'] : [];
        $result = [
            'status' => $overBudget > 0 ? 'blocked' : 'ok',
            'budget_id' => $budget->getKey(),
            'allocated_amount' => round($allocated, 4),
            'committed_amount' => round($committedSpent['committed'], 4),
            'spent_amount' => round($committedSpent['spent'], 4),
            'available_amount' => round($available, 4),
            'estimated_amount' => round($estimated, 4),
            'over_budget_amount' => round($overBudget, 4),
            'currency' => $currency,
            'warnings' => $warnings,
            'blocking_reasons' => $blockingReasons,
        ];

        $this->auditLogService->write('procurement_budget_checked', $budget, null, null, $result, [], $company->getKey());

        return $result;
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array{committed: float, spent: float}
     */
    private function committedAndSpent(Company $company, ProcurementBudget $budget, array $context): array
    {
        $orders = SupplierOrder::query()
            ->select(['id', 'company_id', 'supplier_id', 'status', 'order_date'])
            ->with(['items:id,supplier_order_id,product_id,ordered_quantity,unit_price,currency'])
            ->where('company_id', $company->getKey())
            ->whereBetween('order_date', [$budget->date_from, $budget->date_to])
            ->when(isset($context['supplier_id']), fn ($query) => $query->where('supplier_id', $context['supplier_id']))
            ->limit(5000)
            ->get();

        $committed = 0.0;
        $spent = 0.0;

        foreach ($orders as $order) {
            $amount = $order->items->sum(fn ($item): float => (float) $item->ordered_quantity * (float) ($item->unit_price ?? 0));
            $status = $order->status instanceof \BackedEnum ? $order->status->value : (string) $order->status;

            if ($status === SupplierOrderStatus::Completed->value) {
                $spent += $amount;
            } elseif ($status !== SupplierOrderStatus::Cancelled->value) {
                $committed += $amount;
            }
        }

        return ['committed' => $committed, 'spent' => $spent];
    }
}
