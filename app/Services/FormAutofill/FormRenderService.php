<?php

namespace App\Services\FormAutofill;

use App\Enums\FormAutofillRunStatus;
use App\Exceptions\NotConfiguredYetException;
use App\Models\AuditLog;
use App\Models\FormAutofillOutput;
use App\Models\FormAutofillRun;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class FormRenderService
{
    /**
     * @param  array<string, mixed>  $options
     */
    public function renderInternalHtml(FormAutofillRun $run, User $user, array $options = []): FormAutofillOutput
    {
        $run->loadMissing(['formTemplate', 'fieldValues']);
        $html = '<form>';

        foreach ($run->fieldValues as $field) {
            $html .= sprintf(
                '<label>%s<input name="%s" value="%s"></label>',
                e($field->field_key),
                e($field->field_key),
                e((string) $field->final_value),
            );
        }

        $html .= '</form>';

        return $this->createOutput($run, $user, 'internal_html', [
            'html' => $html,
        ]);
    }

    public function exportJson(FormAutofillRun $run, User $user): FormAutofillOutput
    {
        $payload = $this->payload($run);
        $path = sprintf('form-autofill/%s/run-%s.json', now()->format('Ymd'), $run->id);

        Storage::put($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $this->createOutput($run, $user, 'json', $payload, $path, 'form-autofill-run-'.$run->id.'.json');
    }

    public function exportCsv(FormAutofillRun $run, User $user): FormAutofillOutput
    {
        $run->loadMissing('fieldValues');
        $handle = fopen('php://temp', 'w+b');
        fputcsv($handle, ['field_key', 'final_value', 'confidence', 'source_excerpt']);

        foreach ($run->fieldValues as $field) {
            fputcsv($handle, [$field->field_key, $field->final_value, $field->confidence, $field->source_excerpt]);
        }

        rewind($handle);
        $content = (string) stream_get_contents($handle);
        fclose($handle);

        $path = sprintf('form-autofill/%s/run-%s.csv', now()->format('Ymd'), $run->id);
        Storage::put($path, $content);

        return $this->createOutput($run, $user, 'csv', ['rows' => $this->payload($run)['fields']], $path, 'form-autofill-run-'.$run->id.'.csv');
    }

    public function preparePlaceholder(string $type): never
    {
        throw new NotConfiguredYetException("Form autofill renderer [{$type}] is not configured yet.");
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(FormAutofillRun $run): array
    {
        $run->loadMissing(['formTemplate', 'fieldValues']);

        return [
            'run_id' => $run->id,
            'status' => $run->status->value,
            'template' => [
                'code' => $run->formTemplate?->code,
                'context_type' => $run->formTemplate?->context_type?->value,
            ],
            'fields' => $run->fieldValues->map(fn ($field): array => [
                'field_key' => $field->field_key,
                'final_value' => $field->final_value,
                'confidence' => $field->confidence,
                'source_excerpt' => $field->source_excerpt,
            ])->values()->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $content
     */
    private function createOutput(
        FormAutofillRun $run,
        User $user,
        string $type,
        array $content,
        ?string $path = null,
        ?string $filename = null,
    ): FormAutofillOutput {
        $output = $run->outputs()->create([
            'output_type' => $type,
            'filename' => $filename,
            'stored_path' => $path,
            'content_json' => $content,
            'status' => 'ready',
            'created_by_user_id' => $user->id,
        ]);

        $run->forceFill([
            'status' => FormAutofillRunStatus::Exported,
        ])->save();

        AuditLog::query()->create([
            'company_id' => $run->company_id,
            'user_id' => $user->id,
            'event_type' => 'form_autofill_run.exported',
            'auditable_type' => $run::class,
            'auditable_id' => $run->id,
            'old_values_json' => [],
            'new_values_json' => [
                'output_id' => $output->id,
                'output_type' => $type,
            ],
            'metadata_json' => [],
            'created_at' => now(),
        ]);

        return $output;
    }
}
