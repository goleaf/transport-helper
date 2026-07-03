<?php

namespace App\Http\Requests\Supply;

use App\Models\ProcurementPolicy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProcurementPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ProcurementPolicy::class) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:255'],
            'status' => ['nullable', 'string', Rule::in(['active', 'inactive', 'archived'])],
            'enforcement_mode' => ['required', 'string', Rule::in(['advisory', 'enforced'])],
            'default_currency' => ['required', 'string', 'size:3'],
            'rules_json' => ['nullable', 'array'],
            'approval_thresholds_json' => ['nullable', 'array'],
            'supplier_rules_json' => ['nullable', 'array'],
            'budget_rules_json' => ['nullable', 'array'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }
}
