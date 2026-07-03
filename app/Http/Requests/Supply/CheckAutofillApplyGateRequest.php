<?php

namespace App\Http\Requests\Supply;

use Illuminate\Foundation\Http\FormRequest;

class CheckAutofillApplyGateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageSupplyWorkflow()
            || ($this->user()?->hasPermissionTo('apply_email_form_autofill') ?? false);
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'confirmation' => ['nullable', 'boolean'],
        ];
    }
}
