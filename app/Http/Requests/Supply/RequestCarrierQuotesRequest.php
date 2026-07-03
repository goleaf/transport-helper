<?php

namespace App\Http\Requests\Supply;

use Illuminate\Foundation\Http\FormRequest;

class RequestCarrierQuotesRequest extends FormRequest
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
            'carrier_ids' => ['nullable', 'array'],
            'carrier_ids.*' => ['integer', 'exists:carriers,id'],
            'required_pickup_date' => ['nullable', 'date'],
            'required_delivery_date' => ['nullable', 'date'],
            'message' => ['nullable', 'string'],
        ];
    }
}
