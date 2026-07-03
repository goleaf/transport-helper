<?php

namespace App\Services\Supply\Forecasting;

use App\Models\Company;
use App\Models\Product;
use App\Models\SalesExclusionRule;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use InvalidArgumentException;

class SalesExclusionService
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    /**
     * @param  array<string, mixed>  $validated
     * @return array{rule: SalesExclusionRule}
     */
    public function createRule(array $validated, User $user): array
    {
        $this->validateReasonAndDates($validated);

        $rule = SalesExclusionRule::query()->create($validated + [
            'created_by_user_id' => $user->getKey(),
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $this->auditLogService->write('sales_exclusion_rule_created', $rule, $user, null, [
            'rule_type' => $this->scalar($rule->rule_type),
            'applies_to' => $rule->applies_to,
            'reason' => $rule->reason,
        ], [], $rule->company_id);

        return ['rule' => $rule];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{rule: SalesExclusionRule}
     */
    public function updateRule(SalesExclusionRule $rule, array $validated, User $user): array
    {
        $this->validateReasonAndDates($validated + $rule->getAttributes());
        $old = $rule->getOriginal();
        $rule->fill($validated);
        $rule->save();

        $this->auditLogService->write('sales_exclusion_rule_updated', $rule, $user, $old, $rule->getChanges(), [
            'reason' => $rule->reason,
        ], $rule->company_id);

        return ['rule' => $rule->refresh()];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return list<SalesExclusionRule>
     */
    public function matchingRules(Company $company, Product $product, string $dateFrom, string $dateTo, array $context = []): array
    {
        $supplierId = $context['supplier_id'] ?? null;
        $appliesTo = $context['applies_to'] ?? null;

        return SalesExclusionRule::query()
            ->select(['id', 'company_id', 'supplier_id', 'product_id', 'category', 'rule_type', 'date_from', 'date_to', 'applies_to', 'reason', 'is_active'])
            ->where('company_id', $company->getKey())
            ->where('is_active', true)
            ->whereDate('date_from', '<=', $dateTo)
            ->whereDate('date_to', '>=', $dateFrom)
            ->where(function ($query) use ($product): void {
                $query->whereNull('product_id')->orWhere('product_id', $product->getKey());
            })
            ->where(function ($query) use ($product): void {
                $query->whereNull('category')->orWhere('category', $product->category);
            })
            ->where(function ($query) use ($supplierId): void {
                $query->whereNull('supplier_id');

                if ($supplierId !== null) {
                    $query->orWhere('supplier_id', $supplierId);
                }
            })
            ->when($appliesTo !== null, function ($query) use ($appliesTo): void {
                $query->where(function ($inner) use ($appliesTo): void {
                    $inner->where('applies_to', 'all_calculation_periods')
                        ->orWhere('applies_to', $appliesTo);
                });
            })
            ->orderBy('date_from')
            ->orderBy('id')
            ->limit(200)
            ->get()
            ->all();
    }

    /**
     * @param  list<SalesExclusionRule>  $rules
     * @param  list<mixed>  $salesRows
     * @return array<string, mixed>
     */
    public function explainExclusions(array $rules, array $salesRows = []): array
    {
        return [
            'rules_count' => count($rules),
            'sales_rows_considered' => count($salesRows),
            'rules' => collect($rules)->map(fn (SalesExclusionRule $rule): array => [
                'id' => $rule->getKey(),
                'type' => $this->scalar($rule->rule_type),
                'scope' => [
                    'supplier_id' => $rule->supplier_id,
                    'product_id' => $rule->product_id,
                    'category' => $rule->category,
                ],
                'date_from' => $rule->date_from?->toDateString(),
                'date_to' => $rule->date_to?->toDateString(),
                'applies_to' => $rule->applies_to,
                'reason' => $rule->reason,
            ])->values()->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function validateReasonAndDates(array $data): void
    {
        if (trim((string) ($data['reason'] ?? '')) === '') {
            throw new InvalidArgumentException('Sales exclusion rule requires a reason.');
        }

        if (($data['date_from'] ?? null) > ($data['date_to'] ?? null)) {
            throw new InvalidArgumentException('Sales exclusion rule date_from must be before date_to.');
        }
    }

    private function scalar(mixed $value): mixed
    {
        return $value instanceof \BackedEnum ? $value->value : $value;
    }
}
