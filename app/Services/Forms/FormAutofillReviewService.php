<?php

namespace App\Services\Forms;

use App\Enums\FormAutofillRunStatus;
use App\Models\FormAutofillFieldValue;
use App\Models\FormAutofillRun;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FormAutofillReviewService
{
    public function __construct(
        private readonly FormFieldNormalizationService $normalizationService,
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function acceptField(FormAutofillFieldValue $field, User $user): array
    {
        $this->assertRunEditable($field);
        $value = $field->normalized_value ?? $field->extracted_value;

        return $this->updateFieldState($field, $user, [
            'final_value' => $value,
            'requires_review' => false,
            'review_reason' => null,
            'accepted_by_user_id' => $user->id,
            'accepted_at' => now(),
        ], 'form_autofill_field_accepted');
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public function updateField(FormAutofillFieldValue $field, array $validated, User $user): array
    {
        $this->assertRunEditable($field);
        $templateField = $this->templateField($field);
        $fieldType = $templateField?->field_type instanceof \BackedEnum ? $templateField->field_type->value : (string) ($templateField?->field_type ?? 'text');
        $normalization = $this->normalizationService->normalizeByFieldType($fieldType, $validated['final_value'] ?? null);

        if (! $normalization['success']) {
            throw ValidationException::withMessages([
                'final_value' => $normalization['error'] ?? 'Invalid field value.',
            ]);
        }

        return $this->updateFieldState($field, $user, [
            'final_value' => $normalization['value'],
            'requires_review' => false,
            'review_reason' => null,
            'accepted_by_user_id' => $user->id,
            'accepted_at' => now(),
        ], 'form_autofill_field_edited', [
            'reason' => $validated['reason'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public function rejectField(FormAutofillFieldValue $field, User $user, array $validated = []): array
    {
        $this->assertRunEditable($field);

        return $this->updateFieldState($field, $user, [
            'final_value' => null,
            'requires_review' => true,
            'review_reason' => $validated['reason'] ?? 'rejected_by_user',
            'accepted_by_user_id' => null,
            'accepted_at' => null,
        ], 'form_autofill_field_rejected', [
            'reason' => $validated['reason'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function validateRun(FormAutofillRun $run, User $user, array $options = []): array
    {
        return DB::transaction(function () use ($run, $user, $options): array {
            $run->loadMissing(['formTemplate.fields', 'fieldValues']);
            $errors = [];
            $fieldsByKey = $run->fieldValues->keyBy('field_key');

            if (! in_array($run->status, [FormAutofillRunStatus::AiFilled, FormAutofillRunStatus::NeedsReview], true)) {
                $errors[] = 'run_status_not_validatable';
            }

            foreach ($run->formTemplate->fields as $templateField) {
                $field = $fieldsByKey->get($templateField->field_key);
                $finalValue = $field?->final_value;

                if ($templateField->is_required && ($field === null || $finalValue === null || $finalValue === '')) {
                    $errors[] = 'required_field_missing_'.$templateField->field_key;
                }

                if ($templateField->is_required && $field?->requires_review) {
                    $errors[] = 'required_field_unresolved_'.$templateField->field_key;
                }

                if (! $templateField->is_required && $field?->requires_review && ($options['ignore_optional_review'] ?? false) !== true) {
                    $errors[] = 'optional_field_unresolved_'.$templateField->field_key;
                }

                if ($field?->requires_review && str_contains((string) $field->review_reason, 'quantity_mismatch') && ($options['mismatch_reviewed'] ?? false) !== true) {
                    $errors[] = 'quantity_mismatch_unresolved_'.$templateField->field_key;
                }
            }

            $oldValues = $run->only(['status', 'validation_errors_json', 'reviewed_by_user_id']);
            $status = $errors === [] ? FormAutofillRunStatus::Validated : FormAutofillRunStatus::NeedsReview;

            $run->forceFill([
                'status' => $status,
                'validation_errors_json' => $errors,
                'reviewed_by_user_id' => $user->id,
            ])->save();

            $this->auditLogService->write(
                $errors === [] ? 'form_autofill_run_validated' : 'form_autofill_run_validation_failed',
                $run,
                $user,
                $oldValues,
                $run->only(['status', 'validation_errors_json', 'reviewed_by_user_id']),
                [
                    'run_id' => $run->id,
                    'errors' => $errors,
                    'validation_note' => $options['validation_note'] ?? null,
                ],
                $run->company_id,
            );

            return [
                'run' => $run->refresh(),
                'status' => $run->status->value,
                'errors' => $errors,
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public function rejectRun(FormAutofillRun $run, User $user, array $validated = []): array
    {
        return DB::transaction(function () use ($run, $user, $validated): array {
            $oldValues = $run->only(['status', 'reviewed_by_user_id']);

            $run->forceFill([
                'status' => FormAutofillRunStatus::Rejected,
                'reviewed_by_user_id' => $user->id,
            ])->save();

            $this->auditLogService->write('form_autofill_run_rejected', $run, $user, $oldValues, $run->only(['status', 'reviewed_by_user_id']), [
                'run_id' => $run->id,
                'reason' => $validated['reason'] ?? null,
            ], $run->company_id);

            return [
                'run' => $run->refresh(),
                'status' => $run->status->value,
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $changes
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    private function updateFieldState(FormAutofillFieldValue $field, User $user, array $changes, string $eventType, array $metadata = []): array
    {
        return DB::transaction(function () use ($field, $user, $changes, $eventType, $metadata): array {
            $field->loadMissing('formAutofillRun');
            $run = $field->formAutofillRun;
            $oldValues = $field->only(['final_value', 'requires_review', 'review_reason', 'accepted_by_user_id', 'accepted_at']);

            $field->forceFill($changes)->save();

            $userChanges = is_array($run->user_changes_json) ? $run->user_changes_json : [];
            $userChanges[$field->field_key] = [
                'final_value' => $field->final_value,
                'changed_by_user_id' => $user->id,
                'changed_at' => now()->toDateTimeString(),
                'event_type' => $eventType,
                'reason' => $metadata['reason'] ?? null,
            ];

            if ($run->status !== FormAutofillRunStatus::Rejected) {
                $run->forceFill([
                    'user_changes_json' => $userChanges,
                    'status' => FormAutofillRunStatus::NeedsReview,
                ])->save();
            }

            $this->auditLogService->write($eventType, $field, $user, $oldValues, $field->only(['final_value', 'requires_review', 'review_reason', 'accepted_by_user_id', 'accepted_at']), [
                'run_id' => $run->id,
                'field_id' => $field->id,
                'field_key' => $field->field_key,
                'old_final_value' => $oldValues['final_value'] ?? null,
                'new_final_value' => $field->final_value,
                'confidence' => $field->confidence,
                'reason' => $metadata['reason'] ?? null,
                'review_reason' => $field->review_reason,
            ], $run->company_id);

            return [
                'field' => $field->refresh(),
                'run' => $run->refresh(),
            ];
        });
    }

    private function assertRunEditable(FormAutofillFieldValue $field): void
    {
        $field->loadMissing('formAutofillRun');

        if (in_array($field->formAutofillRun->status, [FormAutofillRunStatus::Rejected, FormAutofillRunStatus::Applied], true)) {
            throw ValidationException::withMessages([
                'run' => 'This autofill run cannot be edited.',
            ]);
        }
    }

    private function templateField(FormAutofillFieldValue $field): mixed
    {
        $field->loadMissing('formAutofillRun.formTemplate.fields');

        return $field->formAutofillRun->formTemplate->fields->firstWhere('field_key', $field->field_key);
    }
}
