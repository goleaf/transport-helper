<?php

namespace App\Http\Requests\Supply;

use App\Models\ProcurementException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProcurementExceptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ProcurementException::class) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'exceptable_type' => ['required', 'string', Rule::in(['proposal', 'supplier_order', 'scenario'])],
            'exceptable_id' => ['required', 'integer', 'min:1'],
            'exception_type' => ['required', 'string', Rule::in([
                'budget_overrun',
                'missing_price',
                'supplier_minimum_not_met',
                'supplier_maximum_exceeded',
                'order_frequency_violation',
                'urgent_purchase',
                'manual_override',
                'other',
            ])],
            'reason' => ['required', 'string', 'min:3', 'max:10000'],
        ];
    }
}
