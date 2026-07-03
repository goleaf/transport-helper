<?php

namespace App\Http\Requests\Supply;

use Illuminate\Foundation\Http\FormRequest;

class ApplyAiCarrierQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageLogisticsWorkflow() ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'supplier_order_id' => ['nullable', 'integer', 'exists:supplier_orders,id'],
            'allow_unknown_carrier' => ['nullable', 'boolean'],
            'allow_missing_delivery_date' => ['nullable', 'boolean'],
            'allow_zero_price' => ['nullable', 'boolean'],
            'confirm_apply' => ['nullable', 'accepted'],
        ];
    }
}
