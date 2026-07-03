<?php

namespace App\Services\Supply\Transport;

use App\Enums\LogisticsStatus;
use App\Models\CarrierQuote;
use App\Models\LogisticsRecord;
use Carbon\Carbon;

class TransportLogisticsUpdater
{
    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function updateAfterSelection(CarrierQuote $quote, array $options = []): array
    {
        $quote->loadMissing(['supplierOrder.supplier']);
        $order = $quote->supplierOrder;
        $record = LogisticsRecord::query()->firstOrCreate([
            'company_id' => $quote->company_id,
            'supplier_order_id' => $order->id,
        ], [
            'supplier_id' => $order->supplier_id,
            'order_date' => $order->order_date,
            'status' => LogisticsStatus::Planned,
            'currency' => $quote->currency ?? $order->supplier?->default_currency,
        ]);
        $oldValues = $record->only(['carrier_id', 'pickup_date', 'delivery_date', 'transport_price', 'currency', 'selected_carrier_quote_id', 'status', 'notes']);
        $status = $this->status($quote, $record, $options);
        $notes = trim(implode("\n", array_filter([
            $record->notes,
            'Carrier quote '.$quote->id.' selected for transport.',
        ])));

        $record->forceFill([
            'carrier_id' => $quote->carrier_id,
            'pickup_date' => $quote->pickup_date ?? $record->pickup_date,
            'delivery_date' => $quote->delivery_date ?? $record->delivery_date,
            'transport_price' => $quote->price ?? $record->transport_price,
            'currency' => $quote->currency ?? $record->currency,
            'selected_carrier_quote_id' => $quote->id,
            'status' => $status,
            'notes' => $notes,
        ])->save();

        return [
            'record' => $record->refresh(),
            'old_values' => $oldValues,
            'new_values' => $record->only(['carrier_id', 'pickup_date', 'delivery_date', 'transport_price', 'currency', 'selected_carrier_quote_id', 'status', 'notes']),
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function status(CarrierQuote $quote, LogisticsRecord $record, array $options): LogisticsStatus
    {
        if ($options['override_needs_review'] ?? false) {
            return LogisticsStatus::NeedsReview;
        }

        $requiredDeliveryDate = $this->date($options['required_delivery_date'] ?? null);
        $deliveryDate = $this->date($quote->delivery_date);

        if ($requiredDeliveryDate instanceof Carbon && $deliveryDate instanceof Carbon && $deliveryDate->gt($requiredDeliveryDate)) {
            return LogisticsStatus::Delayed;
        }

        if ($quote->pickup_date !== null) {
            return LogisticsStatus::PickupScheduled;
        }

        if ($record->ready_date !== null) {
            return LogisticsStatus::ReadyForPickup;
        }

        return LogisticsStatus::Confirmed;
    }

    private function date(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse((string) $value)->startOfDay();
    }
}
