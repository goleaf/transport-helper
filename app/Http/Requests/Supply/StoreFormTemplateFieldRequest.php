<?php

namespace App\Http\Requests\Supply;

use App\Enums\FormFieldType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFormTemplateFieldRequest extends FormRequest
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
            'field_key' => ['required', 'string', 'max:255'],
            'label' => ['required', 'string', 'max:255'],
            'field_type' => ['required', 'string', Rule::in(array_column(FormFieldType::cases(), 'value'))],
            'is_required' => ['sometimes', 'boolean'],
            'validation_rules_json' => ['nullable', 'array'],
            'ai_extraction_hint' => ['nullable', 'string'],
            'default_value_json' => ['nullable', 'array'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
