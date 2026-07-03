<?php

namespace App\Http\Requests\Supply;

use Illuminate\Foundation\Http\FormRequest;

class ScoreCarrierQuotesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->canManageLogisticsWorkflow() ?? false)
            || ($this->user()?->hasPermissionTo('select_carrier') ?? false);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'required_pickup_date' => ['nullable', 'date'],
            'required_delivery_date' => ['nullable', 'date'],
            'price_weight' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'delivery_date_weight' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'pickup_date_weight' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'reliability_weight' => ['nullable', 'numeric', 'min:0', 'max:1'],
        ];
    }
}
