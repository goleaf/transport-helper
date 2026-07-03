<?php

namespace App\Services\Supply\Procurement;

use App\Enums\ProcurementEnforcementMode;
use App\Models\Company;
use App\Models\ProcurementPolicy;
use App\Models\Supplier;

class ProcurementPolicyResolver
{
    public function __construct(private readonly ProcurementPolicyService $policyService) {}

    /**
     * @param  array<string, mixed>  $context
     * @return array{policy: ProcurementPolicy|null, enforcement_mode: string, rules: array<string, mixed>, approval_thresholds: list<array<string, mixed>>, supplier_rules: array<string, mixed>, budget_rules: array<string, mixed>, warnings: list<string>}
     */
    public function resolve(Company $company, ?Supplier $supplier = null, array $context = []): array
    {
        $policy = $this->policyService->defaultPolicy($company);

        if (! $policy instanceof ProcurementPolicy) {
            return [
                'policy' => null,
                'enforcement_mode' => (string) config('supply.procurement.default_enforcement_mode', ProcurementEnforcementMode::Advisory->value),
                'rules' => [
                    'block_missing_price_in_enforced_mode' => (bool) config('supply.procurement.block_missing_price_in_enforced_mode', true),
                    'allow_self_approval' => (bool) config('supply.procurement.allow_self_approval', false),
                    'default_currency' => (string) config('supply.procurement.default_currency', 'EUR'),
                ],
                'approval_thresholds' => [],
                'supplier_rules' => [],
                'budget_rules' => [],
                'warnings' => ['no_procurement_policy'],
            ];
        }

        return [
            'policy' => $policy,
            'enforcement_mode' => $policy->enforcement_mode?->value ?? (string) $policy->enforcement_mode,
            'rules' => array_merge([
                'block_missing_price_in_enforced_mode' => (bool) config('supply.procurement.block_missing_price_in_enforced_mode', true),
                'allow_self_approval' => (bool) config('supply.procurement.allow_self_approval', false),
                'default_currency' => $policy->default_currency,
            ], $policy->rules_json ?? []),
            'approval_thresholds' => array_values($policy->approval_thresholds_json ?? []),
            'supplier_rules' => $this->supplierRules($policy, $supplier),
            'budget_rules' => $policy->budget_rules_json ?? [],
            'warnings' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function supplierRules(ProcurementPolicy $policy, ?Supplier $supplier): array
    {
        $rules = $policy->supplier_rules_json ?? [];
        $supplierRules = $rules['suppliers'] ?? [];

        if ($supplier instanceof Supplier && is_array($supplierRules)) {
            foreach ($supplierRules as $rule) {
                if (is_array($rule) && (int) ($rule['supplier_id'] ?? 0) === (int) $supplier->getKey()) {
                    return array_merge($rules, $rule);
                }
            }
        }

        unset($rules['suppliers']);

        return $rules;
    }
}
