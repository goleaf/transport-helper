<?php

namespace App\Http\Requests\Supply;

use Illuminate\Foundation\Http\FormRequest;

class CreateEmailFormAutofillRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageSupplyWorkflow() ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'form_template_id' => ['required', 'integer', 'exists:form_templates,id'],
            'instructions' => ['nullable', 'array'],
        ];
    }
}
