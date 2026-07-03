<?php

namespace App\View\Components\Supply;

use App\Models\FormAutofillRun;
use App\Support\DisplayValue;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class FormAutofillFieldsTable extends Component
{
    public array $rows;

    public function __construct(public FormAutofillRun $run)
    {
        $templateFields = $run->formTemplate?->fields?->keyBy('field_key') ?? collect();

        $this->rows = $run->fieldValues
            ->map(function ($field) use ($templateFields): array {
                $templateField = $templateFields->get($field->field_key);
                $finalValue = DisplayValue::normalize($field->final_value);
                $reviewStatus = $field->requires_review ? 'Needs review' : 'Resolved';
                $reviewReason = DisplayValue::scalar($field->review_reason);

                return [
                    'field' => $field,
                    'label' => $templateField?->label ?? $field->field_key,
                    'field_key' => $field->field_key,
                    'field_type' => DisplayValue::humanLabel($templateField?->field_type),
                    'review_text' => trim($reviewStatus.' '.$reviewReason),
                    'final_input_value' => is_scalar($finalValue) || $finalValue === null ? (string) ($finalValue ?? '') : '',
                ];
            })
            ->values()
            ->all();
    }

    public function render(): View
    {
        return view('components.supply.form-autofill-fields-table');
    }
}
