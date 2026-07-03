<?php

namespace App\Services\Supply\Analytics;

use App\Models\FormAutofillFieldValue;
use App\Models\FormAutofillRun;
use App\Models\User;

class FormAutofillQualityReportService
{
    public function __construct(private readonly AnalyticsFilterService $filters) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function report(array $filters = [], ?User $user = null): array
    {
        $normalized = $this->filters->normalize($filters);
        $runs = FormAutofillRun::query()
            ->select(['id', 'status', 'confidence', 'created_at', 'applied_at'])
            ->with(['fieldValues:id,form_autofill_run_id,field_key,extracted_value,normalized_value,final_value,confidence,source_excerpt,requires_review'])
            ->latest('id')
            ->limit(500)
            ->get();
        $fields = $runs->flatMap->fieldValues;
        $edited = $fields->filter(fn (FormAutofillFieldValue $field): bool => $field->final_value !== $field->extracted_value && $field->final_value !== $field->normalized_value);
        $lowConfidence = $fields->filter(fn (FormAutofillFieldValue $field): bool => (float) $field->confidence < 80);

        return [
            'type' => 'form_autofill_quality',
            'title' => 'Form Autofill Quality',
            'description' => 'Field correction, validation and low confidence analytics.',
            'filters' => $normalized,
            'summary' => [
                'total_autofill_runs' => $runs->count(),
                'validated' => $runs->filter(fn (FormAutofillRun $run): bool => $this->status($run->status) === 'validated')->count(),
                'rejected' => $runs->filter(fn (FormAutofillRun $run): bool => $this->status($run->status) === 'rejected')->count(),
                'applied' => $runs->filter(fn (FormAutofillRun $run): bool => $this->status($run->status) === 'applied' || $run->applied_at !== null)->count(),
                'fields_total' => $fields->count(),
                'fields_accepted' => $fields->where('requires_review', false)->count(),
                'fields_edited' => $edited->count(),
                'fields_rejected' => 0,
                'correction_rate' => $this->percentage($edited->count(), $fields->count()),
                'low_confidence_fields' => $lowConfidence->count(),
                'source_excerpt_missing_count' => $fields->filter(fn (FormAutofillFieldValue $field): bool => blank($field->source_excerpt))->count(),
                'average_time_to_validation_hours' => 0.0,
            ],
            'most_corrected_fields' => $edited->groupBy('field_key')->map(fn ($group, string $field): array => ['field' => $field, 'count' => $group->count()])->values()->all(),
            'rows' => $fields->map(fn (FormAutofillFieldValue $field): array => [
                'field_key' => $field->field_key,
                'confidence' => (float) $field->confidence,
                'requires_review' => (bool) $field->requires_review,
                'was_edited' => $field->final_value !== $field->extracted_value && $field->final_value !== $field->normalized_value,
            ])->values()->all(),
            'warnings' => array_merge($normalized['warnings'], $runs->isEmpty() ? ['No form autofill runs found.'] : []),
            'definitions' => app(KpiDefinitionService::class)->definitions(),
        ];
    }

    private function percentage(int|float $value, int|float $total): float
    {
        return $total > 0 ? round(($value / $total) * 100, 2) : 0.0;
    }

    private function status(mixed $status): string
    {
        return $status instanceof \BackedEnum ? $status->value : (string) $status;
    }
}
