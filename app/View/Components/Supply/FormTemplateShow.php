<?php

namespace App\View\Components\Supply;

use App\Models\FormTemplate;
use App\Support\DisplayValue;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class FormTemplateShow extends Component
{
    public string $companyName;

    public string $supplierName;

    public string $carrierName;

    public string $contextLabel;

    public string $formatLabel;

    public string $statusValue;

    public string $statusLabel;

    public int $fieldCount;

    public int $requiredFieldCount;

    public int $optionalFieldCount;

    public int $autofillRunCount;

    public int $nextSortOrder;

    public array $fieldRows;

    public array $fieldTypeOptions;

    public function __construct(
        public FormTemplate $template,
        array $fieldTypes = []
    ) {
        $this->companyName = DisplayValue::scalar($template->company?->name, 'Not linked');
        $this->supplierName = DisplayValue::scalar($template->supplier?->name, 'Any supplier');
        $this->carrierName = DisplayValue::scalar($template->carrier?->name, 'Any carrier');
        $this->contextLabel = DisplayValue::humanLabel($template->context_type);
        $this->formatLabel = DisplayValue::humanLabel($template->format_type);
        $this->statusValue = $template->is_active ? 'active' : 'inactive';
        $this->statusLabel = $template->is_active ? 'Active' : 'Inactive';
        $this->fieldCount = (int) ($template->fields_count ?? $template->fields->count());
        $this->requiredFieldCount = $template->fields->where('is_required', true)->count();
        $this->optionalFieldCount = $this->fieldCount - $this->requiredFieldCount;
        $this->autofillRunCount = (int) ($template->autofill_runs_count ?? 0);
        $this->nextSortOrder = ((int) $template->fields->max('sort_order')) + 10;
        $this->fieldRows = $this->fieldRows();
        $this->fieldTypeOptions = collect($fieldTypes)
            ->map(fn (mixed $fieldType): array => [
                'value' => DisplayValue::statusValue($fieldType),
                'label' => DisplayValue::humanLabel($fieldType),
            ])
            ->values()
            ->all();
    }

    public function render(): View
    {
        return view('components.supply.form-template-show');
    }

    private function fieldRows(): array
    {
        return $this->template->fields
            ->map(fn ($field): array => [
                'key' => $field->field_key,
                'label' => $field->label,
                'type_label' => DisplayValue::humanLabel($field->field_type),
                'requirement_status' => $field->is_required ? 'required' : 'optional',
                'requirement_label' => $field->is_required ? 'Required' : 'Optional',
                'sort_order' => $field->sort_order,
                'hint' => DisplayValue::scalar($field->ai_extraction_hint, 'No extraction hint yet.'),
            ])
            ->values()
            ->all();
    }
}
