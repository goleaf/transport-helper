<?php

namespace App\Http\Requests\Supply;

use App\Enums\SalesExclusionRuleType;
use App\Models\SalesExclusionRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSalesExclusionRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        $rule = $this->route('rule');

        return $rule instanceof SalesExclusionRule
            ? $user->can('update', $rule)
            : $user->can('create', SalesExclusionRule::class);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'category' => ['nullable', 'string', 'max:255'],
            'rule_type' => ['required', 'string', Rule::in(array_map(fn (SalesExclusionRuleType $type): string => $type->value, SalesExclusionRuleType::cases()))],
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'applies_to' => ['required', 'string', Rule::in(['trend_period', 't0_t1', 't1_t2', 't2_t3', 'all_calculation_periods'])],
            'reason' => ['required', 'string', 'min:3', 'max:10000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
