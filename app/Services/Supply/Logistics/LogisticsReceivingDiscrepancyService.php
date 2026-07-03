<?php

namespace App\Services\Supply\Logistics;

use App\Enums\ReceivingDiscrepancyType;
use App\Models\SupplierOrder;

class LogisticsReceivingDiscrepancyService
{
    /**
     * @param  list<array<string, mixed>>  $receivedItems
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function detect(SupplierOrder $order, array $receivedItems, array $options = []): array
    {
        $order->loadMissing('items.product:id,sku,name');
        $completeOrder = (bool) ($options['complete_order'] ?? false);
        $discrepancies = [];
        $matchedItemIds = [];

        foreach ($receivedItems as $receivedItem) {
            $orderItem = $this->matchOrderItem($order, $receivedItem);

            if ($orderItem === null) {
                $discrepancies[] = $this->discrepancy(ReceivingDiscrepancyType::UnexpectedItem, 'blocking', $receivedItem, 'Received item is not part of the supplier order.');

                continue;
            }

            $matchedItemIds[] = $orderItem->id;
            $expected = (float) ($orderItem->confirmed_quantity ?? $orderItem->ordered_quantity);
            $received = (float) ($receivedItem['received_quantity'] ?? 0);
            $damaged = (float) ($receivedItem['damaged_quantity'] ?? 0);

            if ($orderItem->confirmed_quantity === null) {
                $discrepancies[] = $this->discrepancy(ReceivingDiscrepancyType::ReceivedWithoutConfirmation, 'warning', $receivedItem, 'Received item has no supplier confirmed quantity.', $orderItem, $expected, $received, $damaged);
            }

            if ($received < $expected) {
                $discrepancies[] = $this->discrepancy(ReceivingDiscrepancyType::ReceivedLessThanExpected, $completeOrder ? 'blocking' : 'warning', $receivedItem, 'Received quantity is lower than expected.', $orderItem, $expected, $received, $damaged);
            }

            if ($received > $expected) {
                $discrepancies[] = $this->discrepancy(ReceivingDiscrepancyType::ReceivedMoreThanExpected, 'blocking', $receivedItem, 'Received quantity is higher than expected.', $orderItem, $expected, $received, $damaged);
            }

            if ($damaged > 0) {
                $discrepancies[] = $this->discrepancy(ReceivingDiscrepancyType::DamagedQuantity, 'warning', $receivedItem, 'Damaged quantity was reported.', $orderItem, $expected, $received, $damaged);
            }
        }

        if ($completeOrder) {
            foreach ($order->items as $item) {
                if (! in_array($item->id, $matchedItemIds, true)) {
                    $expected = (float) ($item->confirmed_quantity ?? $item->ordered_quantity);
                    $discrepancies[] = $this->discrepancy(ReceivingDiscrepancyType::MissingItem, 'blocking', [], 'Expected item was not included in receipt.', $item, $expected, 0, 0);
                }
            }
        }

        $blocking = collect($discrepancies)->contains(fn (array $item): bool => $item['severity'] === 'blocking');

        return [
            'has_discrepancies' => $discrepancies !== [],
            'blocking' => $blocking,
            'summary' => $discrepancies === [] ? 'No receiving discrepancies.' : implode(' ', collect($discrepancies)->pluck('message')->all()),
            'discrepancies' => $discrepancies,
        ];
    }

    /**
     * @param  array<string, mixed>  $receivedItem
     */
    public function matchOrderItem(SupplierOrder $order, array $receivedItem): mixed
    {
        $order->loadMissing('items.product:id,sku');

        if (isset($receivedItem['product_id'])) {
            return $order->items->firstWhere('product_id', (int) $receivedItem['product_id']);
        }

        $sku = strtoupper(trim((string) ($receivedItem['sku'] ?? '')));

        if ($sku === '') {
            return null;
        }

        return $order->items->first(fn ($item): bool => strtoupper((string) $item->product?->sku) === $sku);
    }

    /**
     * @param  array<string, mixed>  $receivedItem
     * @return array<string, mixed>
     */
    private function discrepancy(
        ReceivingDiscrepancyType $type,
        string $severity,
        array $receivedItem,
        string $message,
        mixed $orderItem = null,
        ?float $expected = null,
        ?float $received = null,
        ?float $damaged = null,
    ): array {
        return [
            'type' => $type->value,
            'severity' => $severity,
            'product_id' => $orderItem?->product_id,
            'sku' => $orderItem?->product?->sku ?? ($receivedItem['sku'] ?? null),
            'expected_quantity' => $expected,
            'received_quantity' => $received ?? (isset($receivedItem['received_quantity']) ? (float) $receivedItem['received_quantity'] : null),
            'damaged_quantity' => $damaged ?? (isset($receivedItem['damaged_quantity']) ? (float) $receivedItem['damaged_quantity'] : null),
            'message' => $message,
        ];
    }
}
