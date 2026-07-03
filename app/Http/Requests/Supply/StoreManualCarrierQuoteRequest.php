<?php

namespace App\Http\Requests\Supply;

use Illuminate\Foundation\Http\FormRequest;

class StoreManualCarrierQuoteRequest extends FormRequest
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
            'supplier_order_id' => ['required', 'integer', 'exists:supplier_orders,id'],
            'carrier_id' => ['nullable', 'integer', 'exists:carriers,id'],
            'carrier_name' => ['nullable', 'string', 'max:255'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'pickup_date' => ['nullable', 'date'],
            'delivery_date' => ['nullable', 'date'],
            'transit_days' => ['nullable', 'integer', 'min:0'],
            'conditions' => ['nullable', 'string'],
            'reliability_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'required_pickup_date' => ['nullable', 'date'],
            'required_delivery_date' => ['nullable', 'date'],
            'scoring_config' => ['nullable', 'array'],
        ];
    }
}
