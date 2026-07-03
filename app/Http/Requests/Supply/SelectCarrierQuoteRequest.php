<?php

namespace App\Http\Requests\Supply;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SelectCarrierQuoteRequest extends FormRequest
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
            'override_needs_review' => ['nullable', 'boolean'],
            'override_reason' => ['nullable', 'string', 'max:5000'],
            'replace_existing' => ['nullable', 'boolean'],
            'reject_others' => ['nullable', 'boolean'],
            'required_pickup_date' => ['nullable', 'date'],
            'required_delivery_date' => ['nullable', 'date'],
            'confirmation' => ['sometimes', 'accepted'],
            'confirm_selection' => ['sometimes', 'accepted'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->boolean('override_needs_review') && ! $this->filled('override_reason')) {
                    $validator->errors()->add('override_reason', 'Override reason is required when selecting a needs-review quote.');
                }
            },
        ];
    }
}
