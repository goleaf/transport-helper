<?php

namespace App\Services\Audit;

use App\Models\AiEmailExtraction;
use App\Models\AuditLog;
use App\Models\CalculationRun;
use App\Models\CarrierQuote;
use App\Models\EmailMessage;
use App\Models\ExportFile;
use App\Models\FormAutofillFieldValue;
use App\Models\FormAutofillRun;
use App\Models\ImportBatch;
use App\Models\OrderProposal;
use App\Models\OrderProposalItem;
use App\Models\SupplierOrderItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Throwable;

class AuditLogService
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function logCreated(Model $model, ?User $user = null, array $metadata = []): AuditLog
    {
        return $this->write(
            $this->buildEventName($model, 'created'),
            $model,
            $user,
            null,
            $model->getAttributes(),
            $metadata,
        );
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     * @param  array<string, mixed>  $metadata
     */
    public function logUpdated(Model $model, array $oldValues, array $newValues, ?User $user = null, array $metadata = []): AuditLog
    {
        return $this->write(
            $this->buildEventName($model, 'updated'),
            $model,
            $user,
            $oldValues,
            $newValues,
            $metadata,
        );
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function logDeleted(Model $model, ?User $user = null, array $metadata = []): AuditLog
    {
        return $this->write(
            $this->buildEventName($model, 'deleted'),
            $model,
            $user,
            $model->getAttributes(),
            null,
            $metadata,
        );
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function logStatusChanged(Model $model, ?string $oldStatus, string $newStatus, ?User $user = null, array $metadata = []): AuditLog
    {
        return $this->write(
            $this->buildEventName($model, 'status_changed'),
            $model,
            $user,
            ['status' => $oldStatus],
            ['status' => $newStatus],
            $metadata,
        );
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function logDecision(string $eventType, Model $model, ?User $user = null, array $metadata = []): AuditLog
    {
        return $this->write($eventType, $model, $user, null, null, $metadata);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function logImport(ImportBatch $batch, string $eventType, ?User $user = null, array $metadata = []): AuditLog
    {
        return $this->write($eventType, $batch, $user, null, [
            'status' => $batch->status,
            'total_rows' => $batch->total_rows,
            'successful_rows' => $batch->successful_rows,
            'failed_rows' => $batch->failed_rows,
        ], $metadata, $batch->company_id);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function logExport(ExportFile $file, string $eventType, ?User $user = null, array $metadata = []): AuditLog
    {
        return $this->write($eventType, $file, $user, null, [
            'export_type' => $file->export_type,
            'filename' => $file->filename,
            'stored_path' => $file->stored_path,
            'status' => $file->status,
        ], $metadata, $file->company_id);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function logCalculationRun(CalculationRun $run, ?User $user = null, array $metadata = []): AuditLog
    {
        $eventType = match ($run->status) {
            'completed', 'completed_with_warnings' => 'calculation_run_completed',
            'failed' => 'calculation_run_failed',
            default => 'calculation_run_created',
        };

        return $this->write($eventType, $run, $user, null, [
            'status' => $run->status,
            'formula_version' => $run->formula_version,
            'calculation_date' => $run->calculation_date?->toDateString(),
            'started_at' => $run->started_at?->toISOString(),
            'finished_at' => $run->finished_at?->toISOString(),
        ], $metadata, $run->company_id);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function logOrderProposalCreated(OrderProposal $proposal, ?User $user = null, array $metadata = []): AuditLog
    {
        return $this->write('order_proposal_created', $proposal, $user, null, [
            'status' => $this->scalarValue($proposal->status),
            'total_lines' => $proposal->total_lines,
            'calculation_run_id' => $proposal->calculation_run_id,
            'supplier_id' => $proposal->supplier_id,
        ], $metadata, $proposal->company_id);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function logOrderProposalItemCalculated(OrderProposalItem $item, ?User $user = null, array $metadata = []): AuditLog
    {
        return $this->write('order_proposal_item_calculated', $item, $user, null, [
            'status' => $this->scalarValue($item->status),
            'raw_need' => $item->raw_need,
            'recommended_quantity' => $item->recommended_quantity,
            'requires_human_review' => $item->requires_human_review,
        ], $metadata);
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
        return $this->write('email_message.received', $emailMessage, $user, null, $emailMessage->only([
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
        return $this->write('ai_email_extraction.created', $extraction, $user, null, $extraction->only([
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
        return $this->write('form_autofill_run.created', $run, $user, null, $run->only([
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
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     * @param  array<string, mixed>  $metadata
     */
    public function write(string $eventType, ?Model $auditable = null, ?User $user = null, ?array $oldValues = null, ?array $newValues = null, array $metadata = [], ?int $companyId = null): AuditLog
    {
        return AuditLog::query()->create([
            'company_id' => $companyId ?? $this->resolveCompanyId($auditable),
            'user_id' => $user?->id,
            'event_type' => $eventType,
            'auditable_type' => $auditable instanceof Model ? $auditable::class : null,
            'auditable_id' => $auditable?->getKey(),
            'old_values_json' => $oldValues,
            'new_values_json' => $newValues,
            'metadata_json' => $metadata,
            'ip_address' => $this->getRequestIp(),
            'user_agent' => $this->getRequestUserAgent(),
            'created_at' => now(),
        ]);
    }

    protected function resolveCompanyId(?Model $model): ?int
    {
        if (! $model instanceof Model) {
            return null;
        }

        $companyId = $model->getAttribute('company_id');

        if (is_numeric($companyId)) {
            return (int) $companyId;
        }

        if ($model->relationLoaded('company') && $model->getRelation('company') instanceof Model) {
            $relatedCompanyId = $model->getRelation('company')->getKey();

            return is_numeric($relatedCompanyId) ? (int) $relatedCompanyId : null;
        }

        if ($model instanceof OrderProposalItem) {
            $proposal = $model->relationLoaded('orderProposal')
                ? $model->orderProposal
                : $model->orderProposal()->select(['id', 'company_id'])->first();

            return is_numeric($proposal?->company_id) ? (int) $proposal->company_id : null;
        }

        if ($model instanceof SupplierOrderItem) {
            $order = $model->relationLoaded('supplierOrder')
                ? $model->supplierOrder
                : $model->supplierOrder()->select(['id', 'company_id'])->first();

            return is_numeric($order?->company_id) ? (int) $order->company_id : null;
        }

        return null;
    }

    protected function getRequestIp(): ?string
    {
        try {
            return request()?->ip();
        } catch (Throwable) {
            return null;
        }
    }

    protected function getRequestUserAgent(): ?string
    {
        try {
            return request()?->userAgent();
        } catch (Throwable) {
            return null;
        }
    }

    protected function buildEventName(Model $model, string $suffix): string
    {
        return Str::of(class_basename($model))
            ->snake()
            ->append('_'.$suffix)
            ->toString();
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
