<?php

namespace App\Services\FormAutofill;

use App\Enums\FormFieldType;
use App\Models\Company;
use App\Models\FormTemplate;
use App\Models\FormTemplateField;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class FormTemplateService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function createTemplate(array $data): FormTemplate
    {
        $this->validateTemplateFieldSchema($data['fields_schema_json'] ?? []);

        return FormTemplate::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateTemplate(FormTemplate $template, array $data): FormTemplate
    {
        if (array_key_exists('fields_schema_json', $data)) {
            $this->validateTemplateFieldSchema($data['fields_schema_json'] ?? []);
        }

        $template->fill($data)->save();

        return $template;
    }

    public function loadActiveTemplateByContext(Company|int $company, string $contextType): ?FormTemplate
    {
        $companyId = $company instanceof Company ? $company->id : $company;

        return FormTemplate::query()
            ->where('company_id', $companyId)
            ->where('context_type', $contextType)
            ->where('is_active', true)
            ->with('fields')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * @return Collection<int, FormTemplateField>
     */
    public function listFields(FormTemplate $template): Collection
    {
        return $template->fields()
            ->orderBy('sort_order')
            ->get();
    }

    public function validateTemplateFieldSchema(mixed $schema): void
    {
        if ($schema === null || $schema === []) {
            return;
        }

        if (! is_array($schema)) {
            throw ValidationException::withMessages([
                'fields_schema_json' => 'Template field schema must be an array.',
            ]);
        }

        if (isset($schema['fields']) && ! is_array($schema['fields'])) {
            throw ValidationException::withMessages([
                'fields_schema_json.fields' => 'Template fields schema must contain an array of fields.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createField(FormTemplate $template, array $data): FormTemplateField
    {
        $fieldType = $data['field_type'] ?? null;

        if (! in_array($fieldType, array_column(FormFieldType::cases(), 'value'), true)) {
            throw ValidationException::withMessages([
                'field_type' => 'Unsupported field type.',
            ]);
        }

        return $template->fields()->create($data);
    }
}
