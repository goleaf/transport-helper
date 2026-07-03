<?php

namespace App\Services\Supply\Procurement;

class ApprovalRequirementService
{
    /**
     * @param  array<string, mixed>  $policy
     * @param  array<string, mixed>  $estimation
     * @param  array<string, mixed>  $budgetCheck
     * @param  array<string, mixed>  $context
     * @return array{requires_approval: bool, requirements: list<array<string, mixed>>, warnings: list<string>}
     */
    public function determine(array $policy, array $estimation, array $budgetCheck, array $context = []): array
    {
        $requirements = [];
        $warnings = [];
        $total = (float) ($estimation['total'] ?? 0);
        $thresholds = $policy['approval_thresholds'] ?? [];

        foreach (is_array($thresholds) ? $thresholds : [] as $threshold) {
            if (! is_array($threshold) || ! $this->thresholdMatches($threshold, $context)) {
                continue;
            }

            $amount = (float) ($threshold['amount'] ?? 0);
            if ($amount > 0 && $total > $amount) {
                $requirements[] = [
                    'type' => 'amount_threshold',
                    'amount' => $total,
                    'threshold' => $amount,
                    'currency' => $threshold['currency'] ?? $estimation['currency'] ?? null,
                    'required_role' => $threshold['required_role'] ?? null,
                    'required_permission' => $threshold['required_permission'] ?? null,
                    'message' => 'Order value exceeds threshold.',
                ];
            }
        }

        if (($budgetCheck['over_budget_amount'] ?? 0) > 0) {
            $requirements[] = [
                'type' => 'budget_overrun',
                'amount' => $budgetCheck['estimated_amount'] ?? $total,
                'threshold' => $budgetCheck['available_amount'] ?? 0,
                'currency' => $budgetCheck['currency'] ?? $estimation['currency'] ?? null,
                'required_role' => 'admin',
                'required_permission' => 'manage_settings',
                'message' => 'Order value exceeds available budget.',
            ];
        }

        $rules = $policy['rules'] ?? [];
        if (($estimation['missing_price_count'] ?? 0) > 0 && (bool) ($rules['missing_price_requires_approval'] ?? true)) {
            $requirements[] = [
                'type' => 'missing_price',
                'amount' => $total,
                'threshold' => 0,
                'currency' => $estimation['currency'] ?? null,
                'required_role' => 'admin',
                'required_permission' => 'manage_settings',
                'message' => 'One or more order lines have missing prices.',
            ];
        }

        if ($requirements !== []) {
            $warnings[] = 'procurement_approval_required';
        }

        return [
            'requires_approval' => $requirements !== [],
            'requirements' => $requirements,
            'warnings' => $warnings,
        ];
    }

    /**
     * @param  array<string, mixed>  $threshold
     * @param  array<string, mixed>  $context
     */
    private function thresholdMatches(array $threshold, array $context): bool
    {
        $scope = $threshold['scope'] ?? 'company';

        return match ($scope) {
            'supplier' => (int) ($threshold['supplier_id'] ?? 0) === (int) ($context['supplier_id'] ?? 0),
            'category' => (string) ($threshold['category'] ?? '') === (string) ($context['category'] ?? ''),
            'product' => in_array((int) ($threshold['product_id'] ?? 0), collect($context['product_ids'] ?? [])->map(fn (mixed $id): int => (int) $id)->all(), true),
            default => true,
        };
    }
}
