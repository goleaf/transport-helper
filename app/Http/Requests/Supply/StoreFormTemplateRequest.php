<?php

namespace App\Http\Requests\Supply;

use App\Enums\FormTemplateContextType;
use App\Enums\FormTemplateFormatType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFormTemplateRequest extends FormRequest
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
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255'],
            'context_type' => ['required', 'string', Rule::in(array_column(FormTemplateContextType::cases(), 'value'))],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'carrier_id' => ['nullable', 'integer', 'exists:carriers,id'],
            'format_type' => ['required', 'string', Rule::in(array_column(FormTemplateFormatType::cases(), 'value'))],
            'version' => ['required', 'string', 'max:50'],
            'fields_schema_json' => ['nullable', 'array'],
            'mapping_rules_json' => ['nullable', 'array'],
            'validation_rules_json' => ['nullable', 'array'],
            'renderer_config_json' => ['nullable', 'array'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
