<?php

namespace App\Services\Forms;

use App\Enums\FormFieldType;
use App\Models\Company;
use App\Models\FormTemplate;
use App\Models\FormTemplateField;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class FormTemplateService
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public function createTemplate(array $validated, ?User $user = null): array
    {
        $validated['version'] = $validated['version'] ?? '1';

        $this->ensureUniqueTemplate($validated);
        $this->validateTemplateFieldSchema($validated['fields_schema_json'] ?? []);

        $template = FormTemplate::query()->create($validated);

        $this->auditLogService->write('form_template_created', $template, $user, null, $template->getAttributes(), [
            'form_template_id' => $template->id,
            'code' => $template->code,
            'version' => $template->version,
        ], $template->company_id);

        return ['template' => $template];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public function updateTemplate(FormTemplate $template, array $validated, ?User $user = null): array
    {
        $this->ensureUniqueTemplate($validated + [
            'company_id' => $template->company_id,
            'code' => $template->code,
            'version' => $template->version,
        ], $template);

        if (array_key_exists('fields_schema_json', $validated)) {
            $this->validateTemplateFieldSchema($validated['fields_schema_json'] ?? []);
        }

        $oldValues = $template->only(array_keys($validated));
        $template->fill($validated)->save();

        $this->auditLogService->write('form_template_updated', $template, $user, $oldValues, $template->only(array_keys($validated)), [
            'form_template_id' => $template->id,
        ], $template->company_id);

        return ['template' => $template->refresh()];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public function addField(FormTemplate $template, array $validated, ?User $user = null): array
    {
        if (! in_array($validated['field_type'] ?? null, array_column(FormFieldType::cases(), 'value'), true)) {
            throw ValidationException::withMessages([
                'field_type' => 'Unsupported field type.',
            ]);
        }

        $exists = $template->fields()
            ->where('field_key', $validated['field_key'])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'field_key' => 'Field key already exists for this template.',
            ]);
        }

        $field = $template->fields()->create($validated);

        $this->auditLogService->write('form_template_field_created', $field, $user, null, $field->getAttributes(), [
            'form_template_id' => $template->id,
            'field_key' => $field->field_key,
        ], $template->company_id);

        return ['field' => $field];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, FormTemplate>|Collection<int, FormTemplate>
     */
    public function activeTemplatesForContext(Company $company, string $contextType, array $filters = []): LengthAwarePaginator|Collection
    {
        $query = FormTemplate::query()
            ->select(['id', 'company_id', 'name', 'code', 'context_type', 'format_type', 'version', 'is_active'])
            ->where('company_id', $company->id)
            ->where('context_type', $contextType)
            ->where('is_active', true)
            ->withCount('fields')
            ->orderBy('name');

        return isset($filters['paginate'])
            ? $query->paginate((int) $filters['paginate'])->withQueryString()
            : $query->get();
    }

    /**
     * Compatibility method for older callers.
     *
     * @param  array<string, mixed>  $data
     */
    public function createField(FormTemplate $template, array $data): FormTemplateField
    {
        return $this->addField($template, $data)['field'];
    }

    /**
     * Compatibility method for older callers.
     *
     * @param  array<string, mixed>  $data
     */
    public function createTemplateModel(array $data): FormTemplate
    {
        return $this->createTemplate($data)['template'];
    }

    /**
     * Compatibility method for older callers.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateTemplateModel(FormTemplate $template, array $data): FormTemplate
    {
        return $this->updateTemplate($template, $data)['template'];
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
    private function ensureUniqueTemplate(array $data, ?FormTemplate $ignore = null): void
    {
        $companyId = $data['company_id'] ?? null;
        $code = $data['code'] ?? null;
        $version = $data['version'] ?? '1';

        if ($companyId === null || $code === null) {
            return;
        }

        $exists = FormTemplate::query()
            ->where('company_id', $companyId)
            ->where('code', $code)
            ->where('version', $version)
            ->when($ignore instanceof FormTemplate, fn ($query) => $query->whereKeyNot($ignore->id))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'code' => 'Template code and version must be unique for the company.',
            ]);
        }
    }
}
