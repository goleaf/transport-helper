<?php

namespace App\Services\Supply\Logistics;

use App\Enums\LogisticsStatus;
use App\Enums\SupplierConfirmationStatus;
use App\Enums\SupplierOrderStatus;
use App\Models\LogisticsRecord;
use Carbon\CarbonInterface;

class LogisticsStatusResolver
{
    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function suggestStatus(LogisticsRecord $record, array $context = []): array
    {
        $record->loadMissing([
            'supplierOrder.items.product:id,sku',
            'supplierConfirmation:id,status',
        ]);

        $today = ($context['today'] ?? null) instanceof CarbonInterface ? $context['today'] : now();
        $reasons = [];
        $warnings = [];
        $suggested = $record->status instanceof LogisticsStatus ? $record->status : LogisticsStatus::Planned;

        if ($this->confirmationNeedsReview($record)) {
            $suggested = LogisticsStatus::NeedsReview;
            $reasons[] = 'supplier_confirmation_needs_review';
        } elseif ($record->actual_received_date !== null) {
            if ($this->hasReceivingMismatch($record)) {
                $suggested = LogisticsStatus::NeedsReview;
                $reasons[] = 'receipt_has_mismatch';
            } elseif ($this->allItemsReceived($record)) {
                $suggested = LogisticsStatus::Completed;
                $reasons[] = 'receipt_reconciled';
            } else {
                $suggested = LogisticsStatus::Arrived;
                $reasons[] = 'receipt_recorded_not_fully_reconciled';
            }
        } elseif ($this->hasConfirmation($record) && $record->ready_date === null) {
            $suggested = LogisticsStatus::WaitingForReadyDate;
            $reasons[] = 'confirmation_exists_ready_date_missing';
        } elseif (! $this->hasConfirmation($record) && $record->supplierOrder?->status === SupplierOrderStatus::Sent) {
            $suggested = LogisticsStatus::OrderSent;
            $reasons[] = 'supplier_order_sent_without_confirmation';
        } elseif ($record->delivery_date !== null && $record->delivery_date->lt($today->copy()->startOfDay())) {
            $suggested = LogisticsStatus::Delayed;
            $reasons[] = 'delivery_date_passed_without_receipt';
        } elseif ($record->pickup_date !== null && $record->pickup_date->lte($today->copy()->startOfDay())) {
            $suggested = LogisticsStatus::InTransit;
            $reasons[] = 'pickup_date_passed_without_receipt';
        } elseif ($record->pickup_date !== null && $record->pickup_date->gt($today->copy()->startOfDay())) {
            $suggested = LogisticsStatus::PickupScheduled;
            $reasons[] = 'pickup_date_scheduled';
        } elseif ($record->ready_date !== null && $record->ready_date->lte($today->copy()->startOfDay())) {
            $suggested = LogisticsStatus::ReadyForPickup;
            $reasons[] = 'ready_date_reached_without_pickup';
        } else {
            $suggested = LogisticsStatus::Planned;
            $reasons[] = 'default_planned';
        }

        $current = $record->status instanceof LogisticsStatus ? $record->status->value : (string) $record->status;

        return [
            'suggested_status' => $suggested->value,
            'current_status' => $current,
            'should_update' => $current !== $suggested->value,
            'reasons' => $reasons,
            'warnings' => $warnings,
        ];
    }

    private function confirmationNeedsReview(LogisticsRecord $record): bool
    {
        return $record->supplierConfirmation?->status === SupplierConfirmationStatus::NeedsReview;
    }

    private function hasConfirmation(LogisticsRecord $record): bool
    {
        return $record->supplier_confirmation_id !== null || $record->confirmation_date !== null;
    }

    private function hasReceivingMismatch(LogisticsRecord $record): bool
    {
        return filled($record->receiving_discrepancies_json);
    }

    private function allItemsReceived(LogisticsRecord $record): bool
    {
        $items = $record->supplierOrder?->items ?? collect();

        if ($items->isEmpty()) {
            return false;
        }

        return $items->every(function ($item): bool {
            $expected = $item->confirmed_quantity ?? $item->ordered_quantity;

            return $item->received_quantity !== null && (float) $item->received_quantity >= (float) $expected;
        });
    }
}
