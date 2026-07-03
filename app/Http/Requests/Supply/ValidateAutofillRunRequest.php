<?php

namespace App\Http\Requests\Supply;

use Illuminate\Foundation\Http\FormRequest;

class ValidateAutofillRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageSupplyWorkflow() ?? false;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'ignore_optional_review' => ['nullable', 'boolean'],
            'mismatch_reviewed' => ['nullable', 'boolean'],
            'validation_note' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
