<?php

namespace App\Services\Supply\Confirmations;

use App\Enums\LogisticsStatus;
use App\Models\LogisticsRecord;
use App\Models\SupplierConfirmation;
use App\Models\SupplierOrder;

class SupplierConfirmationLogisticsUpdater
{
    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function updateLogistics(SupplierOrder $order, SupplierConfirmation $confirmation, LogisticsStatus|string $logisticsStatus, array $options = []): array
    {
        $status = $logisticsStatus instanceof LogisticsStatus ? $logisticsStatus : LogisticsStatus::from($logisticsStatus);

        $record = LogisticsRecord::query()
            ->where('company_id', $order->company_id)
            ->where('supplier_order_id', $order->getKey())
            ->first();

        if (! $record instanceof LogisticsRecord) {
            $record = LogisticsRecord::query()->create([
                'company_id' => $order->company_id,
                'supplier_order_id' => $order->getKey(),
                'supplier_id' => $order->supplier_id,
                'order_date' => $order->order_date,
                'status' => LogisticsStatus::Planned,
            ]);
        }

        $record->forceFill([
            'supplier_id' => $order->supplier_id,
            'supplier_confirmation_id' => $confirmation->getKey(),
            'confirmation_date' => $confirmation->confirmation_date ?? $record->confirmation_date,
            'ready_date' => $confirmation->ready_date ?? $record->ready_date,
            'delivery_date' => $confirmation->expected_arrival_date ?? $record->delivery_date,
            'status' => $status,
            'notes' => trim((string) ($record->notes ?? '')."\nSupplier confirmation #{$confirmation->getKey()} applied."),
        ])->save();

        return [
            'logistics_record' => $record->refresh(),
        ];
    }
}
