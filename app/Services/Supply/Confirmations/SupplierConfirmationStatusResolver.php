<?php

namespace App\Services\Supply\Confirmations;

use App\Enums\LogisticsStatus;
use App\Enums\SupplierConfirmationStatus;
use App\Enums\SupplierOrderStatus;

class SupplierConfirmationStatusResolver
{
    /**
     * @param  array<string, mixed>  $discrepancyResult
     * @param  list<array<string, mixed>>  $matchedItems
     * @param  array<string, mixed>  $normalizedConfirmation
     * @return array<string, mixed>
     */
    public function resolve(array $discrepancyResult, array $matchedItems, array $normalizedConfirmation = []): array
    {
        $types = collect($discrepancyResult['discrepancies'] ?? [])
            ->pluck('type')
            ->filter()
            ->values()
            ->all();

        if (($discrepancyResult['blocking'] ?? false) === true || $this->hasAny($types, [
            'unknown_sku',
            'ambiguous_sku',
            'invalid_date',
            'ambiguous_date',
            'additional_item',
            'missing_confirmed_quantity',
            'date_conflict',
            'quantity_higher_than_ordered',
            'missing_supplier_order',
            'duplicate_application',
        ])) {
            return [
                'supplier_confirmation_status' => SupplierConfirmationStatus::NeedsReview,
                'supplier_order_status' => SupplierOrderStatus::NeedsReview,
                'logistics_status' => LogisticsStatus::NeedsReview,
            ];
        }

        if ($this->hasAny($types, ['quantity_lower_than_ordered', 'missing_item'])) {
            return [
                'supplier_confirmation_status' => SupplierConfirmationStatus::QuantityMismatch,
                'supplier_order_status' => SupplierOrderStatus::PartiallyConfirmed,
                'logistics_status' => $this->logisticsStatus($normalizedConfirmation, LogisticsStatus::NeedsReview),
            ];
        }

        if ($this->hasAny($types, ['delayed_ready_date', 'delayed_arrival_date', 'date_changed'])) {
            return [
                'supplier_confirmation_status' => SupplierConfirmationStatus::DateMismatch,
                'supplier_order_status' => SupplierOrderStatus::Delayed,
                'logistics_status' => LogisticsStatus::Delayed,
            ];
        }

        return [
            'supplier_confirmation_status' => SupplierConfirmationStatus::Confirmed,
            'supplier_order_status' => SupplierOrderStatus::Confirmed,
            'logistics_status' => $this->logisticsStatus($normalizedConfirmation, LogisticsStatus::Confirmed),
        ];
    }

    /**
     * @param  list<string>  $types
     * @param  list<string>  $needles
     */
    private function hasAny(array $types, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (in_array($needle, $types, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $normalizedConfirmation
     */
    private function logisticsStatus(array $normalizedConfirmation, LogisticsStatus $fallback): LogisticsStatus
    {
        if (($normalizedConfirmation['ready_date'] ?? null) === null) {
            return LogisticsStatus::WaitingForReadyDate;
        }

        return $fallback;
    }
}
