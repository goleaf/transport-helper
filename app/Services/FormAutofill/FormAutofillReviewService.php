<?php

namespace App\Services\FormAutofill;

use App\Enums\FormAutofillRunStatus;
use App\Models\AuditLog;
use App\Models\FormAutofillFieldValue;
use App\Models\FormAutofillRun;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class FormAutofillReviewService
{
    /**
     * @return array<string, mixed>
     */
    public function acceptField(FormAutofillFieldValue $field, User $user): array
    {
        return $this->updateFieldState($field, [
            'final_value' => $field->normalized_value ?? $field->extracted_value,
            'requires_review' => false,
            'review_reason' => null,
            'accepted_by_user_id' => $user->id,
            'accepted_at' => now(),
        ], $user, 'form_autofill_field.accepted');
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public function updateField(FormAutofillFieldValue $field, array $validated, User $user): array
    {
        return $this->updateFieldState($field, [
            'final_value' => $validated['final_value'],
            'requires_review' => false,
            'review_reason' => null,
            'accepted_by_user_id' => $user->id,
            'accepted_at' => now(),
        ], $user, 'form_autofill_field.updated');
    }

    /**
     * @return array<string, mixed>
     */
    public function rejectField(FormAutofillFieldValue $field, User $user): array
    {
        return $this->updateFieldState($field, [
            'final_value' => null,
            'requires_review' => true,
            'review_reason' => 'rejected_by_user',
            'accepted_by_user_id' => $user->id,
            'accepted_at' => now(),
        ], $user, 'form_autofill_field.rejected');
    }

    /**
     * @return array<string, mixed>
     */
    public function validateRun(FormAutofillRun $run, User $user): array
    {
        return DB::transaction(function () use ($run, $user): array {
            $run->loadMissing(['formTemplate.fields', 'fieldValues']);
            $errors = [];
            $fieldsByKey = $run->fieldValues->keyBy('field_key');

            foreach ($run->formTemplate->fields as $templateField) {
                $field = $fieldsByKey->get($templateField->field_key);

                if ($templateField->is_required && ($field === null || $field->final_value === null || $field->final_value === '')) {
                    $errors[] = 'required_field_missing_'.$templateField->field_key;
                }

                if ($templateField->is_required && $field?->requires_review) {
                    $errors[] = 'required_field_unresolved_'.$templateField->field_key;
                }
            }

            $oldValues = $this->runAuditValues($run);
            $status = $errors === [] ? FormAutofillRunStatus::Validated : FormAutofillRunStatus::NeedsReview;

            $run->forceFill([
                'status' => $status,
                'validation_errors_json' => $errors,
                'reviewed_by_user_id' => $user->id,
            ])->save();

            $this->writeAuditLog('form_autofill_run.validated', $run, $user, $oldValues, $this->runAuditValues($run));

            return [
                'status' => $run->status->value,
                'errors' => $errors,
                'run' => $run->refresh(),
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function rejectRun(FormAutofillRun $run, User $user): array
    {
        return DB::transaction(function () use ($run, $user): array {
            $oldValues = $this->runAuditValues($run);

            $run->forceFill([
                'status' => FormAutofillRunStatus::Rejected,
                'reviewed_by_user_id' => $user->id,
            ])->save();

            $this->writeAuditLog('form_autofill_run.rejected', $run, $user, $oldValues, $this->runAuditValues($run));

            return [
                'status' => $run->status->value,
                'run' => $run,
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $changes
     * @return array<string, mixed>
     */
    private function updateFieldState(FormAutofillFieldValue $field, array $changes, User $user, string $eventType): array
    {
        return DB::transaction(function () use ($field, $changes, $user, $eventType): array {
            $field->loadMissing('formAutofillRun');
            $oldValues = $field->only(['final_value', 'requires_review', 'review_reason', 'accepted_by_user_id', 'accepted_at']);

            $field->forceFill($changes)->save();

            $run = $field->formAutofillRun;
            $userChanges = is_array($run->user_changes_json) ? $run->user_changes_json : [];
            $userChanges[$field->field_key] = [
                'final_value' => $field->final_value,
                'changed_by_user_id' => $user->id,
                'changed_at' => now()->toDateTimeString(),
                'event_type' => $eventType,
            ];
            $run->forceFill([
                'user_changes_json' => $userChanges,
                'status' => FormAutofillRunStatus::NeedsReview,
            ])->save();

            $this->writeAuditLog($eventType, $run, $user, $oldValues, $field->only(['final_value', 'requires_review', 'review_reason', 'accepted_by_user_id', 'accepted_at']));

            return [
                'field' => $field->refresh(),
                'run' => $run->refresh(),
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function runAuditValues(FormAutofillRun $run): array
    {
        return $run->only(['status', 'validation_errors_json', 'warnings_json', 'reviewed_by_user_id', 'applied_by_user_id', 'applied_at']);
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    private function writeAuditLog(string $eventType, FormAutofillRun $run, User $user, array $oldValues, array $newValues): void
    {
        AuditLog::query()->create([
            'company_id' => $run->company_id,
            'user_id' => $user->id,
            'event_type' => $eventType,
            'auditable_type' => $run::class,
            'auditable_id' => $run->id,
            'old_values_json' => $oldValues,
            'new_values_json' => $newValues,
            'metadata_json' => [],
            'created_at' => now(),
        ]);
    }
}
