<?php

namespace App\Services\Supply\Confirmations;

use App\Models\LogisticsRecord;
use App\Models\SupplierOrder;
use App\Models\SupplierOrderItem;
use Carbon\Carbon;

class SupplierConfirmationDiscrepancyService
{
    /**
     * @param  list<array<string, mixed>>  $matchedItems
     * @param  array<string, mixed>  $normalizedConfirmation
     * @return array<string, mixed>
     */
    public function detect(SupplierOrder $order, array $matchedItems, array $normalizedConfirmation): array
    {
        $order->loadMissing('items.product', 'logisticsRecords');
        $discrepancies = [];
        $affectedProductIds = [];
        $matchedOrderItemIds = [];

        foreach ($normalizedConfirmation['warnings'] ?? [] as $warning) {
            $type = str_contains((string) $warning, 'invalid_date') ? 'invalid_date' : 'ambiguous_date';
            $discrepancies[] = $this->discrepancy($type, 'blocking', ['message' => 'Supplier confirmation contains an invalid or ambiguous date.']);
        }

        foreach ($matchedItems as $matchedItem) {
            if (($matchedItem['matched'] ?? false) !== true) {
                $sourceItem = is_array($matchedItem['source_item'] ?? null) ? $matchedItem['source_item'] : [];
                $type = ($matchedItem['ambiguous'] ?? false) ? 'ambiguous_sku' : 'unknown_sku';

                $discrepancies[] = $this->discrepancy($type, 'blocking', [
                    'sku' => $sourceItem['sku'] ?? $sourceItem['manufacturer_sku'] ?? $sourceItem['supplier_sku'] ?? null,
                    'message' => $type === 'ambiguous_sku'
                        ? 'Supplier item matches more than one order line.'
                        : 'Supplier item could not be matched to the order.',
                ]);
                $discrepancies[] = $this->discrepancy('additional_item', 'blocking', [
                    'sku' => $sourceItem['sku'] ?? $sourceItem['manufacturer_sku'] ?? $sourceItem['supplier_sku'] ?? null,
                    'message' => 'Supplier confirmed an item that is not safely matched to the order.',
                ]);

                continue;
            }

            $orderItem = $matchedItem['supplier_order_item'] ?? null;

            if (! $orderItem instanceof SupplierOrderItem) {
                continue;
            }

            $matchedOrderItemIds[] = $orderItem->getKey();
            $affectedProductIds[] = $orderItem->product_id;
            $orderedQuantity = (float) $orderItem->ordered_quantity;
            $confirmedQuantity = $matchedItem['confirmed_quantity'] ?? null;

            if ($confirmedQuantity === null || $confirmedQuantity === '') {
                $discrepancies[] = $this->discrepancy('missing_confirmed_quantity', 'blocking', [
                    'product_id' => $orderItem->product_id,
                    'sku' => $orderItem->product?->sku,
                    'ordered_quantity' => $orderedQuantity,
                    'message' => 'Confirmed quantity is missing.',
                ]);

                continue;
            }

            $confirmedQuantity = (float) $confirmedQuantity;

            if ($confirmedQuantity < $orderedQuantity) {
                $discrepancies[] = $this->discrepancy('quantity_lower_than_ordered', 'warning', [
                    'product_id' => $orderItem->product_id,
                    'sku' => $orderItem->product?->sku,
                    'ordered_quantity' => $orderedQuantity,
                    'confirmed_quantity' => $confirmedQuantity,
                    'discrepancy_quantity' => $confirmedQuantity - $orderedQuantity,
                    'message' => "Supplier confirmed {$confirmedQuantity} but ordered quantity is {$orderedQuantity}.",
                ]);
            }

            if ($confirmedQuantity > $orderedQuantity) {
                $discrepancies[] = $this->discrepancy('quantity_higher_than_ordered', 'blocking', [
                    'product_id' => $orderItem->product_id,
                    'sku' => $orderItem->product?->sku,
                    'ordered_quantity' => $orderedQuantity,
                    'confirmed_quantity' => $confirmedQuantity,
                    'discrepancy_quantity' => $confirmedQuantity - $orderedQuantity,
                    'message' => "Supplier confirmed {$confirmedQuantity} but ordered quantity is {$orderedQuantity}.",
                ]);
            }
        }

        foreach ($order->items as $orderItem) {
            if (! in_array($orderItem->getKey(), $matchedOrderItemIds, true)) {
                $affectedProductIds[] = $orderItem->product_id;
                $discrepancies[] = $this->discrepancy('missing_item', 'warning', [
                    'product_id' => $orderItem->product_id,
                    'sku' => $orderItem->product?->sku,
                    'ordered_quantity' => (float) $orderItem->ordered_quantity,
                    'message' => 'Ordered item is missing from supplier confirmation.',
                ]);
            }
        }

        $this->detectDateDiscrepancies($order, $normalizedConfirmation, $discrepancies);

        $blocking = collect($discrepancies)->contains(fn (array $discrepancy): bool => ($discrepancy['severity'] ?? null) === 'blocking');

        return [
            'has_discrepancies' => $discrepancies !== [],
            'blocking' => $blocking,
            'summary' => $this->summary($discrepancies),
            'discrepancies' => $discrepancies,
            'affected_product_ids' => array_values(array_unique(array_filter($affectedProductIds))),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $discrepancies
     */
    private function detectDateDiscrepancies(SupplierOrder $order, array $normalizedConfirmation, array &$discrepancies): void
    {
        $readyDate = $normalizedConfirmation['ready_date'] ?? null;
        $shippingDate = $normalizedConfirmation['shipping_date'] ?? null;
        $expectedArrivalDate = $normalizedConfirmation['expected_arrival_date'] ?? null;

        if ($readyDate !== null && $shippingDate !== null && Carbon::parse($shippingDate)->lt(Carbon::parse($readyDate))) {
            $discrepancies[] = $this->discrepancy('date_conflict', 'blocking', [
                'message' => 'Shipping date is before ready date.',
            ]);
        }

        if ($shippingDate !== null && $expectedArrivalDate !== null && Carbon::parse($expectedArrivalDate)->lt(Carbon::parse($shippingDate))) {
            $discrepancies[] = $this->discrepancy('date_conflict', 'blocking', [
                'message' => 'Expected arrival date is before shipping date.',
            ]);
        }

        $record = $order->logisticsRecords->sortByDesc('id')->first();

        if (! $record instanceof LogisticsRecord) {
            return;
        }

        $this->detectDelay($record->ready_date?->toDateString(), $readyDate, 'ready_date', 'delayed_ready_date', $discrepancies);
        $this->detectDelay($record->delivery_date?->toDateString(), $expectedArrivalDate, 'expected_arrival_date', 'delayed_arrival_date', $discrepancies);
    }

    /**
     * @param  list<array<string, mixed>>  $discrepancies
     */
    private function detectDelay(?string $oldDate, ?string $newDate, string $field, string $type, array &$discrepancies): void
    {
        if ($oldDate === null || $newDate === null || $oldDate === $newDate) {
            return;
        }

        $discrepancies[] = $this->discrepancy('date_changed', 'warning', [
            'field' => $field,
            'old_date' => $oldDate,
            'new_date' => $newDate,
            'message' => 'Supplier confirmation changed an expected date.',
        ]);

        if (Carbon::parse($newDate)->gt(Carbon::parse($oldDate))) {
            $discrepancies[] = $this->discrepancy($type, 'warning', [
                'field' => $field,
                'old_date' => $oldDate,
                'new_date' => $newDate,
                'message' => 'Supplier confirmation delayed an expected date.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function discrepancy(string $type, string $severity, array $payload = []): array
    {
        return ['type' => $type, 'severity' => $severity] + $payload;
    }

    /**
     * @param  list<array<string, mixed>>  $discrepancies
     */
    private function summary(array $discrepancies): ?string
    {
        if ($discrepancies === []) {
            return null;
        }

        return collect($discrepancies)
            ->pluck('message')
            ->filter()
            ->take(3)
            ->implode(' ');
    }
}
