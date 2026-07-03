<?php

namespace App\Http\Requests\Supply;

use Illuminate\Foundation\Http\FormRequest;

class PrepareCarrierQuoteRequestRequest extends FormRequest
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
            'carrier_ids' => ['required', 'array', 'min:1'],
            'carrier_ids.*' => ['integer', 'exists:carriers,id'],
            'pickup_location' => ['nullable', 'string', 'max:1000'],
            'delivery_location' => ['nullable', 'string', 'max:1000'],
            'ready_date' => ['nullable', 'date'],
            'requested_pickup_date' => ['nullable', 'date'],
            'requested_delivery_date' => ['nullable', 'date'],
            'cargo_description' => ['nullable', 'string', 'max:5000'],
            'pallet_count' => ['nullable', 'integer', 'min:0'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'language' => ['nullable', 'string', 'max:10'],
            'create_email_drafts' => ['nullable', 'boolean'],
        ];
    }
}
