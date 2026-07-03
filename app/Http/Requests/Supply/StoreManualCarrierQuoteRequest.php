<?php

namespace App\Http\Requests\Supply;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreManualCarrierQuoteRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $routeOrder = $this->route('order');

        if (! $this->has('supplier_order_id') && $routeOrder) {
            $this->merge([
                'supplier_order_id' => is_object($routeOrder) && method_exists($routeOrder, 'getKey')
                    ? $routeOrder->getKey()
                    : $routeOrder,
            ]);
        }
    }

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
            'currency' => ['nullable', 'string', 'max:10'],
            'pickup_date' => ['nullable', 'date'],
            'delivery_date' => ['nullable', 'date'],
            'transit_days' => ['nullable', 'integer', 'min:0'],
            'conditions' => ['nullable', 'string', 'max:10000'],
            'reliability_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'allow_unknown_carrier' => ['nullable', 'boolean'],
            'allow_missing_delivery_date' => ['nullable', 'boolean'],
            'allow_zero_price' => ['nullable', 'boolean'],
            'required_pickup_date' => ['nullable', 'date'],
            'required_delivery_date' => ['nullable', 'date'],
            'scoring_config' => ['nullable', 'array'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->filled('carrier_id') && ! $this->filled('carrier_name')) {
                    $validator->errors()->add('carrier_name', 'Carrier or carrier name is required.');
                }

                if ($this->filled('pickup_date') && $this->filled('delivery_date') && strtotime((string) $this->input('delivery_date')) < strtotime((string) $this->input('pickup_date'))) {
                    $validator->errors()->add('delivery_date', 'Delivery date must be on or after pickup date.');
                }
            },
        ];
    }
}
