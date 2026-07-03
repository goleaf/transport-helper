<?php

namespace App\Services\Forms;

use App\Enums\FormAutofillRunStatus;
use App\Models\FormAutofillRun;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class FormAutofillExportService
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function export(FormAutofillRun $run, string $format, array $options = [], ?User $user = null): array
    {
        if (! in_array($format, ['json', 'csv'], true)) {
            throw ValidationException::withMessages([
                'format' => 'Unsupported autofill export format.',
            ]);
        }

        $run->loadMissing(['formTemplate.fields', 'emailMessage', 'fieldValues']);

        if ($run->status !== FormAutofillRunStatus::Validated && ($options['include_review_fields'] ?? false) !== true) {
            throw ValidationException::withMessages([
                'run' => 'Autofill run must be validated before export unless review fields are explicitly included.',
            ]);
        }

        $payload = $this->payload($run, $user);
        $directory = 'form-autofill-outputs/'.$run->id;
        $filename = 'form-autofill-run-'.$run->id.'.'.$format;
        $storedPath = $directory.'/'.$filename;
        $content = $format === 'json' ? $this->json($payload) : $this->csv($run);

        Storage::put($storedPath, $content);

        $output = $run->outputs()->create([
            'output_type' => $format,
            'filename' => $filename,
            'stored_path' => $storedPath,
            'content_json' => $format === 'json' ? $payload : ['rows' => $payload['fields']],
            'status' => 'stored',
            'created_by_user_id' => $user?->id,
        ]);

        $this->auditLogService->write('form_autofill_exported', $output, $user, null, [
            'output_type' => $output->output_type,
            'filename' => $output->filename,
            'stored_path' => $output->stored_path,
            'status' => $output->status,
        ], [
            'run_id' => $run->id,
            'format' => $format,
            'include_review_fields' => (bool) ($options['include_review_fields'] ?? false),
        ], $run->company_id);

        return [
            'output' => $output,
            'run' => $run->refresh(),
            'path' => $storedPath,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(FormAutofillRun $run, ?User $user): array
    {
        return [
            'run' => [
                'id' => $run->id,
                'status' => $run->status->value,
                'confidence' => $run->confidence,
            ],
            'template' => [
                'id' => $run->formTemplate?->id,
                'code' => $run->formTemplate?->code,
                'name' => $run->formTemplate?->name,
                'context_type' => $run->formTemplate?->context_type instanceof \BackedEnum ? $run->formTemplate->context_type->value : $run->formTemplate?->context_type,
            ],
            'email' => [
                'id' => $run->emailMessage?->id,
                'subject' => $run->emailMessage?->subject,
                'from_email' => $run->emailMessage?->from_email,
            ],
            'fields' => $run->fieldValues->map(fn ($field): array => [
                'field_key' => $field->field_key,
                'label' => $run->formTemplate?->fields->firstWhere('field_key', $field->field_key)?->label,
                'extracted_value' => $field->extracted_value,
                'normalized_value' => $field->normalized_value,
                'final_value' => $field->final_value,
                'confidence' => $field->confidence,
                'source_excerpt' => $field->source_excerpt,
                'requires_review' => $field->requires_review,
                'review_reason' => $field->review_reason,
            ])->values()->all(),
            'generated_at' => now()->toISOString(),
            'generated_by_user_id' => $user?->id,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function json(array $payload): string
    {
        return json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    private function csv(FormAutofillRun $run): string
    {
        $handle = fopen('php://temp', 'w+b');
        fputcsv($handle, [
            'field_key',
            'label',
            'extracted_value',
            'normalized_value',
            'final_value',
            'confidence',
            'source_excerpt',
            'requires_review',
            'review_reason',
        ]);

        foreach ($run->fieldValues as $field) {
            fputcsv($handle, [
                $field->field_key,
                $run->formTemplate?->fields->firstWhere('field_key', $field->field_key)?->label,
                $this->scalar($field->extracted_value),
                $this->scalar($field->normalized_value),
                $this->scalar($field->final_value),
                $field->confidence,
                $field->source_excerpt,
                $field->requires_review ? '1' : '0',
                $field->review_reason,
            ]);
        }

        rewind($handle);
        $content = (string) stream_get_contents($handle);
        fclose($handle);

        return $content;
    }

    private function scalar(mixed $value): string
    {
        return is_scalar($value) || $value === null
            ? (string) $value
            : json_encode($value, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
