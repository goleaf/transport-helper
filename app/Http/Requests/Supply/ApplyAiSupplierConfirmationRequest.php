<?php

namespace App\Http\Requests\Supply;

use App\Models\SupplierConfirmation;
use Illuminate\Foundation\Http\FormRequest;

class ApplyAiSupplierConfirmationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('applyFromAiExtraction', SupplierConfirmation::class) ?? false;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'supplier_order_id' => ['nullable', 'integer', 'exists:supplier_orders,id'],
            'update_inbound' => ['nullable', 'boolean'],
            'update_logistics' => ['nullable', 'boolean'],
            'allow_over_confirmation' => ['nullable', 'boolean'],
            'allow_missing_items' => ['nullable', 'boolean'],
            'confirm_apply' => ['accepted'],
        ];
    }
}
