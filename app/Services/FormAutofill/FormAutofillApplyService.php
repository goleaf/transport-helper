<?php

namespace App\Services\FormAutofill;

use App\Enums\CarrierQuoteStatus;
use App\Enums\FormAutofillRunStatus;
use App\Enums\FormTemplateContextType;
use App\Enums\LogisticsStatus;
use App\Enums\SupplierConfirmationStatus;
use App\Models\AuditLog;
use App\Models\Carrier;
use App\Models\CarrierQuote;
use App\Models\FormAutofillOutput;
use App\Models\FormAutofillRun;
use App\Models\LogisticsRecord;
use App\Models\Product;
use App\Models\SupplierConfirmation;
use App\Models\SupplierOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FormAutofillApplyService
{
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
                FormTemplateContextType::SupplierConfirmation => $this->applySupplierConfirmation($run),
                FormTemplateContextType::ReadyDateUpdate => $this->applyReadyDateUpdate($run),
                FormTemplateContextType::QuantityMismatch => $this->applyQuantityMismatch($run),
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

            AuditLog::query()->create([
                'company_id' => $run->company_id,
                'user_id' => $user->id,
                'event_type' => 'form_autofill_run.applied',
                'auditable_type' => $run::class,
                'auditable_id' => $run->id,
                'old_values_json' => $oldValues,
                'new_values_json' => [
                    'status' => $run->status,
                    'applied_by_user_id' => $run->applied_by_user_id,
                    'applied_at' => $run->applied_at,
                    'applied_model_type' => $applied::class,
                    'applied_model_id' => $applied->id,
                ],
                'metadata_json' => [],
                'created_at' => now(),
            ]);

            return [
                'run' => $run->refresh(),
                'applied' => $applied,
            ];
        });
    }

    private function applySupplierConfirmation(FormAutofillRun $run): SupplierConfirmation
    {
        $values = $this->values($run);
        $supplierOrder = $this->supplierOrder($run, $values);

        $confirmation = SupplierConfirmation::query()->create([
            'company_id' => $run->company_id,
            'supplier_order_id' => $supplierOrder->id,
            'email_message_id' => $run->email_message_id,
            'supplier_reference' => $values['supplier_reference'] ?? null,
            'confirmation_date' => now()->toDateString(),
            'ready_date' => $values['ready_date'] ?? null,
            'shipping_date' => $values['shipping_date'] ?? null,
            'expected_arrival_date' => $values['expected_arrival_date'] ?? null,
            'status' => SupplierConfirmationStatus::Confirmed,
            'discrepancy_summary' => null,
            'created_from_form_autofill_run_id' => $run->id,
        ]);

        $product = $this->product($run, $values['sku'] ?? null);
        $quantity = $values['confirmed_quantity'] ?? null;

        if ($product instanceof Product && $quantity !== null) {
            $orderItem = $supplierOrder->items()->whereBelongsTo($product)->first();
            $orderedQuantity = $orderItem?->ordered_quantity ?? $quantity;

            $confirmation->items()->create([
                'product_id' => $product->id,
                'ordered_quantity' => $orderedQuantity,
                'confirmed_quantity' => $quantity,
                'discrepancy_quantity' => (float) $quantity - (float) $orderedQuantity,
                'status' => abs((float) $quantity - (float) $orderedQuantity) > 0.0001 ? 'quantity_mismatch' : 'confirmed',
                'notes' => $values['notes'] ?? null,
            ]);
        }

        return $confirmation->load('items');
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

        return CarrierQuote::query()->create([
            'company_id' => $run->company_id,
            'supplier_order_id' => $supplierOrder->id,
            'carrier_id' => $carrier->id,
            'email_message_id' => $run->email_message_id,
            'price' => $values['price'] ?? null,
            'currency' => $values['currency'] ?? $carrier->default_currency,
            'pickup_date' => $values['pickup_date'] ?? null,
            'delivery_date' => $values['delivery_date'] ?? null,
            'transit_days' => $values['transit_days'] ?? null,
            'conditions' => $values['conditions'] ?? null,
            'status' => CarrierQuoteStatus::Received,
            'created_from_form_autofill_run_id' => $run->id,
        ]);
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

    private function applyQuantityMismatch(FormAutofillRun $run): SupplierConfirmation
    {
        $confirmation = $this->applySupplierConfirmation($run);
        $confirmation->forceFill([
            'status' => SupplierConfirmationStatus::NeedsReview,
            'discrepancy_summary' => 'Created from quantity mismatch form autofill run.',
        ])->save();

        return $confirmation;
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

    private function product(FormAutofillRun $run, mixed $sku): ?Product
    {
        if (! is_string($sku) || $sku === '') {
            return null;
        }

        return Product::query()
            ->where('company_id', $run->company_id)
            ->where('sku', $sku)
            ->first();
    }
}
