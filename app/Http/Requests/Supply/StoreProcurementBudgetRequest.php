<?php

namespace App\Http\Requests\Supply;

use App\Models\ProcurementBudget;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProcurementBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ProcurementBudget::class) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:255'],
            'period_type' => ['required', 'string', Rule::in(['monthly', 'quarterly', 'yearly', 'custom'])],
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'currency' => ['required', 'string', 'size:3'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', Rule::in(['draft', 'active', 'closed', 'archived'])],
            'owner_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
