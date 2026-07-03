<?php

namespace App\Services\Audit;

use App\Models\AiEmailExtraction;
use App\Models\AuditLog;
use App\Models\CalculationRun;
use App\Models\CarrierQuote;
use App\Models\EmailMessage;
use App\Models\FormAutofillFieldValue;
use App\Models\FormAutofillRun;
use App\Models\ImportBatch;
use App\Models\OrderProposalItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AuditLogService
{
    /**
     * @param  array<string, mixed>  $newValues
     * @param  array<string, mixed>  $metadata
     */
    public function logCreated(Model $auditable, ?User $user = null, array $newValues = [], array $metadata = [], ?int $companyId = null): AuditLog
    {
        return $this->write($this->eventType($auditable, 'created'), $auditable, $user, [], $newValues, $metadata, $companyId);
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     * @param  array<string, mixed>  $metadata
     */
    public function logUpdated(Model $auditable, ?User $user = null, array $oldValues = [], array $newValues = [], array $metadata = [], ?int $companyId = null): AuditLog
    {
        return $this->write($this->eventType($auditable, 'updated'), $auditable, $user, $oldValues, $newValues, $metadata, $companyId);
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $metadata
     */
    public function logDeleted(Model $auditable, ?User $user = null, array $oldValues = [], array $metadata = [], ?int $companyId = null): AuditLog
    {
        return $this->write($this->eventType($auditable, 'deleted'), $auditable, $user, $oldValues, [], $metadata, $companyId);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function logStatusChanged(Model $auditable, ?User $user, mixed $oldStatus, mixed $newStatus, array $metadata = [], ?int $companyId = null): AuditLog
    {
        return $this->write($this->eventType($auditable, 'status_changed'), $auditable, $user, [
            'status' => $this->scalarValue($oldStatus),
        ], [
            'status' => $this->scalarValue($newStatus),
        ], $metadata, $companyId);
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     * @param  array<string, mixed>  $metadata
     */
    public function logDecision(Model $auditable, ?User $user, string $decision, array $oldValues = [], array $newValues = [], array $metadata = [], ?int $companyId = null): AuditLog
    {
        return $this->write($this->eventType($auditable, $decision), $auditable, $user, $oldValues, $newValues, $metadata, $companyId);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function logImport(ImportBatch $batch, ?User $user = null, string $eventType = 'import_batch.created', array $metadata = []): AuditLog
    {
        return $this->write($eventType, $batch, $user, [], $batch->only([
            'status',
            'total_rows',
            'successful_rows',
            'failed_rows',
        ]), $metadata, $batch->company_id);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function logExport(Model $auditable, ?User $user = null, array $metadata = [], ?int $companyId = null): AuditLog
    {
        return $this->write($this->eventType($auditable, 'exported'), $auditable, $user, [], [], $metadata, $companyId);
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     * @param  array<string, mixed>  $metadata
     */
    public function logEmailSent(Model $auditable, EmailMessage $emailMessage, ?User $user = null, array $oldValues = [], array $newValues = [], array $metadata = [], ?int $companyId = null): AuditLog
    {
        return $this->write('supplier_order.email_sent', $auditable, $user, $oldValues, $newValues, $metadata + [
            'email_message_id' => $emailMessage->id,
            'provider_message_id' => $emailMessage->message_id,
        ], $companyId);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function logEmailReceived(EmailMessage $emailMessage, ?User $user = null, array $metadata = []): AuditLog
    {
        return $this->write('email_message.received', $emailMessage, $user, [], $emailMessage->only([
            'message_id',
            'thread_id',
            'from_email',
            'subject',
            'received_at',
            'status',
        ]), $metadata, $emailMessage->company_id);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function logAiExtractionCreated(AiEmailExtraction $extraction, ?User $user = null, array $metadata = []): AuditLog
    {
        return $this->write('ai_email_extraction.created', $extraction, $user, [], $extraction->only([
            'provider',
            'model',
            'prompt_version',
            'confidence',
            'requires_human_review',
        ]), $metadata, $this->companyIdFromEmailExtraction($extraction));
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     * @param  array<string, mixed>  $metadata
     */
    public function logAiExtractionReviewed(AiEmailExtraction $extraction, ?User $user, string $decision, array $oldValues = [], array $newValues = [], array $metadata = []): AuditLog
    {
        return $this->write('ai_email_extraction.'.$decision, $extraction, $user, $oldValues, $newValues, $metadata, $this->companyIdFromEmailExtraction($extraction));
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function logFormAutofillCreated(FormAutofillRun $run, ?User $user = null, array $metadata = []): AuditLog
    {
        return $this->write('form_autofill_run.created', $run, $user, [], $run->only([
            'status',
            'confidence',
            'requires_human_review',
        ]), $metadata, $run->company_id);
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     * @param  array<string, mixed>  $metadata
     */
    public function logFormAutofillFieldChanged(FormAutofillFieldValue $field, ?User $user, array $oldValues = [], array $newValues = [], array $metadata = []): AuditLog
    {
        return $this->write('form_autofill_field.updated', $field, $user, $oldValues, $newValues, $metadata, $this->companyIdFromFormField($field));
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     * @param  array<string, mixed>  $metadata
     */
    public function logFormAutofillApplied(FormAutofillRun $run, ?User $user, array $oldValues = [], array $newValues = [], array $metadata = []): AuditLog
    {
        return $this->write('form_autofill_run.applied', $run, $user, $oldValues, $newValues, $metadata, $run->company_id);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function logCalculationRun(CalculationRun $run, ?User $user = null, string $eventType = 'calculation_run.started', array $metadata = []): AuditLog
    {
        return $this->write($eventType, $run, $user, [], $run->only([
            'status',
            'formula_version',
            'calculation_date',
            'started_at',
            'finished_at',
        ]), $metadata, $run->company_id);
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     * @param  array<string, mixed>  $metadata
     */
    public function logOrderQuantityAdjusted(OrderProposalItem $item, ?User $user, array $oldValues = [], array $newValues = [], array $metadata = [], ?int $companyId = null): AuditLog
    {
        return $this->write('order_proposal_item.adjusted', $item, $user, $oldValues, $newValues, $metadata, $companyId);
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     * @param  array<string, mixed>  $metadata
     */
    public function logCarrierSelected(CarrierQuote $quote, ?User $user, array $oldValues = [], array $newValues = [], array $metadata = []): AuditLog
    {
        return $this->write('carrier_quote.selected', $quote, $user, $oldValues, $newValues, $metadata, $quote->company_id);
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     * @param  array<string, mixed>  $metadata
     */
    public function write(string $eventType, ?Model $auditable = null, ?User $user = null, array $oldValues = [], array $newValues = [], array $metadata = [], ?int $companyId = null): AuditLog
    {
        return AuditLog::query()->create([
            'company_id' => $companyId ?? $this->companyIdFromModel($auditable),
            'user_id' => $user?->id,
            'event_type' => $eventType,
            'auditable_type' => $auditable instanceof Model ? $auditable::class : null,
            'auditable_id' => $auditable?->getKey(),
            'old_values_json' => $oldValues,
            'new_values_json' => $newValues,
            'metadata_json' => $metadata,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'created_at' => now(),
        ]);
    }

    private function eventType(Model $auditable, string $action): string
    {
        return Str::of(class_basename($auditable))->snake()->toString().'.'.$action;
    }

    private function companyIdFromModel(?Model $model): ?int
    {
        if (! $model instanceof Model) {
            return null;
        }

        $companyId = $model->getAttribute('company_id');

        return is_numeric($companyId) ? (int) $companyId : null;
    }

    private function companyIdFromEmailExtraction(AiEmailExtraction $extraction): ?int
    {
        return $extraction->emailMessage()
            ->select(['id', 'company_id'])
            ->value('company_id');
    }

    private function companyIdFromFormField(FormAutofillFieldValue $field): ?int
    {
        return $field->formAutofillRun()
            ->select(['id', 'company_id'])
            ->value('company_id');
    }

    private function scalarValue(mixed $value): mixed
    {
        return $value instanceof \BackedEnum ? $value->value : $value;
    }
}
