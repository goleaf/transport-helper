<?php

namespace App\Http\Requests\Supply;

use App\Models\ProcurementBudget;
use Illuminate\Foundation\Http\FormRequest;

class StoreProcurementBudgetLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        $budget = $this->route('budget');

        return $budget instanceof ProcurementBudget && ($this->user()?->can('update', $budget) ?? false);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'category' => ['nullable', 'string', 'max:255'],
            'project_name' => ['nullable', 'string', 'max:255'],
            'manager_name' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'committed_amount' => ['nullable', 'numeric', 'min:0'],
            'spent_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
