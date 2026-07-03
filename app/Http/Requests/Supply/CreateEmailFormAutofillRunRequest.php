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
            'extractor' => ['nullable', 'string', 'in:fake,rule_based,external'],
            'force_new' => ['nullable', 'boolean'],
            'fake_output' => ['nullable', 'array'],
            'include_attachments_summary' => ['nullable', 'boolean'],
        ];
    }
}
