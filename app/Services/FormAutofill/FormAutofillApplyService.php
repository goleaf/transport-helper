<?php

namespace App\Services\FormAutofill;

use App\Enums\FormAutofillRunStatus;
use App\Enums\FormTemplateContextType;
use App\Enums\LogisticsStatus;
use App\Models\Carrier;
use App\Models\CarrierQuote;
use App\Models\FormAutofillOutput;
use App\Models\FormAutofillRun;
use App\Models\LogisticsRecord;
use App\Models\SupplierConfirmation;
use App\Models\SupplierOrder;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\Supply\CarrierQuoteApplicationService;
use App\Services\Supply\SupplierConfirmationApplicationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FormAutofillApplyService
{
    public function __construct(
        private readonly SupplierConfirmationApplicationService $supplierConfirmationApplicationService,
        private readonly CarrierQuoteApplicationService $carrierQuoteApplicationService,
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function apply(FormAutofillRun $run, User $user, array $options = []): array
    {
        return DB::transaction(function () use ($run, $user): array {
            $run->refresh();

            if ($run->status !== FormAutofillRunStatus::Validated) {
                throw ValidationException::withMessages([
                    'run' => 'Autofill run must be validated before it can be applied.',
                ]);
            }

            $run->loadMissing(['fieldValues', 'formTemplate', 'emailMessage.relatedSupplierOrder']);
            $applied = match ($run->formTemplate->context_type) {
                FormTemplateContextType::SupplierConfirmation => $this->applySupplierConfirmation($run, $user),
                FormTemplateContextType::ReadyDateUpdate => $this->applyReadyDateUpdate($run),
                FormTemplateContextType::QuantityMismatch => $this->applyQuantityMismatch($run, $user),
                FormTemplateContextType::CarrierQuote => $this->applyCarrierQuote($run),
                FormTemplateContextType::LogisticsUpdate => $this->applyLogisticsUpdate($run),
                FormTemplateContextType::CustomEmailForm => $this->storeCustomOutput($run, $user),
                default => $this->storeCustomOutput($run, $user),
            };

            $oldValues = $run->only(['status', 'applied_by_user_id', 'applied_at']);
            $run->forceFill([
                'status' => FormAutofillRunStatus::Applied,
                'applied_by_user_id' => $user->id,
                'applied_at' => now(),
            ])->save();

            $this->auditLogService->logFormAutofillApplied(
                run: $run,
                user: $user,
                oldValues: $oldValues,
                newValues: [
                    'status' => $run->status,
                    'applied_by_user_id' => $run->applied_by_user_id,
                    'applied_at' => $run->applied_at,
                    'applied_model_type' => $applied::class,
                    'applied_model_id' => $applied->id,
                ],
            );

            return [
                'run' => $run->refresh(),
                'applied' => $applied,
            ];
        });
    }

    private function applySupplierConfirmation(FormAutofillRun $run, User $user): SupplierConfirmation
    {
        $values = $this->values($run);
        $supplierOrder = $this->supplierOrder($run, $values);
        $result = $this->supplierConfirmationApplicationService->apply([
            'supplier_order_id' => $supplierOrder->id,
            'form_autofill_run_id' => $run->id,
            'manual_confirmation_data' => $values,
            'applied_by_user_id' => $user->id,
        ]);

        return $result['confirmation'];
    }

    private function applyCarrierQuote(FormAutofillRun $run): CarrierQuote
    {
        $values = $this->values($run);
        $supplierOrder = $this->supplierOrder($run, $values);
        $carrier = Carrier::query()->firstOrCreate([
            'company_id' => $run->company_id,
            'name' => $values['carrier_name'] ?? 'Unknown Carrier',
        ], [
            'code' => null,
            'default_currency' => $values['currency'] ?? 'EUR',
            'reliability_score' => null,
            'is_active' => true,
            'notes' => 'Created from form autofill run.',
        ]);

        $result = $this->carrierQuoteApplicationService->create([
            'supplier_order_id' => $supplierOrder->id,
            'carrier_id' => $carrier->id,
            'email_message_id' => $run->email_message_id,
            'price' => $values['price'] ?? null,
            'currency' => $values['currency'] ?? $carrier->default_currency,
            'pickup_date' => $values['pickup_date'] ?? null,
            'delivery_date' => $values['delivery_date'] ?? null,
            'transit_days' => $values['transit_days'] ?? null,
            'conditions' => $values['conditions'] ?? null,
            'reliability_score' => $carrier->reliability_score,
            'form_autofill_run_id' => $run->id,
            'source_type' => 'form_autofill',
        ]);

        return $result['quote'];
    }

    private function applyLogisticsUpdate(FormAutofillRun $run): LogisticsRecord
    {
        $values = $this->values($run);
        $supplierOrder = $this->supplierOrder($run, $values);
        $carrier = isset($values['carrier_name'])
            ? Carrier::query()->firstOrCreate([
                'company_id' => $run->company_id,
                'name' => $values['carrier_name'],
            ], ['is_active' => true])
            : null;

        $record = LogisticsRecord::query()->firstOrCreate([
            'company_id' => $run->company_id,
            'supplier_order_id' => $supplierOrder->id,
        ], [
            'supplier_id' => $supplierOrder->supplier_id,
            'order_date' => $supplierOrder->order_date,
            'status' => LogisticsStatus::Planned,
        ]);

        $record->forceFill([
            'carrier_id' => $carrier?->id ?? $record->carrier_id,
            'ready_date' => $values['ready_date'] ?? $record->ready_date,
            'pickup_date' => $values['pickup_date'] ?? $record->pickup_date,
            'delivery_date' => $values['delivery_date'] ?? $record->delivery_date,
            'transport_price' => $values['transport_price'] ?? $record->transport_price,
            'currency' => $values['currency'] ?? $record->currency,
            'status' => isset($values['status']) ? $values['status'] : $record->status,
            'notes' => $values['notes'] ?? $record->notes,
        ])->save();

        return $record;
    }

    private function applyReadyDateUpdate(FormAutofillRun $run): LogisticsRecord
    {
        return $this->applyLogisticsUpdate($run);
    }

    private function applyQuantityMismatch(FormAutofillRun $run, User $user): SupplierConfirmation
    {
        return $this->applySupplierConfirmation($run, $user);
    }

    private function storeCustomOutput(FormAutofillRun $run, User $user): FormAutofillOutput
    {
        return $run->outputs()->create([
            'output_type' => 'custom_email_form',
            'filename' => null,
            'stored_path' => null,
            'content_json' => $this->values($run),
            'status' => 'ready',
            'created_by_user_id' => $user->id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function values(FormAutofillRun $run): array
    {
        return $run->fieldValues
            ->mapWithKeys(fn ($field): array => [$field->field_key => $field->final_value])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function supplierOrder(FormAutofillRun $run, array $values): SupplierOrder
    {
        $order = $run->emailMessage?->relatedSupplierOrder;

        if ($order instanceof SupplierOrder) {
            return $order;
        }

        $orderNumber = $values['supplier_order_number'] ?? null;

        if (is_string($orderNumber) && $orderNumber !== '') {
            $order = SupplierOrder::query()
                ->where('company_id', $run->company_id)
                ->where('order_number', $orderNumber)
                ->first();

            if ($order instanceof SupplierOrder) {
                return $order;
            }
        }

        throw ValidationException::withMessages([
            'supplier_order_number' => 'A supplier order is required before applying this autofill run.',
        ]);
    }
}
