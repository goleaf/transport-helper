<?php

namespace App\Services\Supply\Procurement;

use App\Models\Supplier;
use App\Models\SupplierOrder;

class SupplierOrderRuleService
{
    /**
     * @param  array<string, mixed>  $estimation
     * @param  array<string, mixed>  $policy
     * @param  array<string, mixed>  $context
     * @return array{status: string, warnings: list<string>, blocking_reasons: list<string>, checks: list<array<string, mixed>>}
     */
    public function checkSupplierRules(Supplier $supplier, array $estimation, array $policy, array $context = []): array
    {
        $rules = $policy['supplier_rules'] ?? [];
        $enforced = ($policy['enforcement_mode'] ?? 'advisory') === 'enforced';
        $total = (float) ($estimation['total'] ?? 0);
        $warnings = [];
        $blocking = [];
        $checks = [];

        $minimum = (float) ($rules['minimum_order_value'] ?? 0);
        if ($minimum > 0 && $total < $minimum) {
            $warnings[] = 'supplier_minimum_not_met';
            $checks[] = ['type' => 'supplier_minimum_order_value', 'required' => $minimum, 'actual' => $total];
            if ($enforced) {
                $blocking[] = 'supplier_minimum_not_met';
            }
        }

        $maximum = (float) ($rules['maximum_order_value_without_approval'] ?? $rules['maximum_order_value'] ?? 0);
        if ($maximum > 0 && $total > $maximum) {
            $warnings[] = 'supplier_maximum_exceeded';
            $checks[] = ['type' => 'supplier_maximum_order_value', 'maximum' => $maximum, 'actual' => $total];
            if ($enforced) {
                $blocking[] = 'supplier_maximum_exceeded';
            }
        }

        $periodDays = (int) ($rules['order_frequency_period_days'] ?? 30);
        $maxOrders = (int) ($rules['maximum_orders_per_period'] ?? 0);
        if ($maxOrders > 0) {
            $orderCount = SupplierOrder::query()
                ->where('supplier_id', $supplier->getKey())
                ->where('created_at', '>=', now()->subDays(max(1, $periodDays)))
                ->count();
            if ($orderCount >= $maxOrders) {
                $warnings[] = 'order_frequency_violation';
                $checks[] = ['type' => 'maximum_orders_per_period', 'maximum' => $maxOrders, 'actual' => $orderCount, 'period_days' => $periodDays];
                if ($enforced) {
                    $blocking[] = 'order_frequency_violation';
                }
            }
        }

        $minimumDays = (int) ($rules['minimum_days_between_orders'] ?? 0);
        if ($minimumDays > 0) {
            $latestOrder = SupplierOrder::query()
                ->select(['id', 'supplier_id', 'created_at'])
                ->where('supplier_id', $supplier->getKey())
                ->latest('created_at')
                ->first();
            if ($latestOrder instanceof SupplierOrder && $latestOrder->created_at !== null && $latestOrder->created_at->gt(now()->subDays($minimumDays))) {
                $warnings[] = 'minimum_days_between_orders_violation';
                $checks[] = ['type' => 'minimum_days_between_orders', 'minimum_days' => $minimumDays, 'latest_order_id' => $latestOrder->getKey()];
                if ($enforced) {
                    $blocking[] = 'minimum_days_between_orders_violation';
                }
            }
        }

        return [
            'status' => $blocking === [] ? ($warnings === [] ? 'ok' : 'warning') : 'blocked',
            'warnings' => array_values(array_unique($warnings)),
            'blocking_reasons' => array_values(array_unique($blocking)),
            'checks' => $checks,
        ];
    }
}
