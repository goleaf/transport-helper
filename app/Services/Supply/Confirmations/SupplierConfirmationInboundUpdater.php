<?php

namespace App\Services\Supply\Confirmations;

use App\Models\InboundOrder;
use App\Models\SupplierConfirmation;
use App\Models\SupplierOrder;

class SupplierConfirmationInboundUpdater
{
    /**
     * @param  list<array<string, mixed>>  $items
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function updateInbound(SupplierOrder $order, SupplierConfirmation $confirmation, array $items, array $options = []): array
    {
        $order->loadMissing('supplier');

        $inboundOrder = InboundOrder::query()
            ->with('items')
            ->where('company_id', $order->company_id)
            ->where(function ($query) use ($order): void {
                $query
                    ->where('supplier_order_id', $order->getKey())
                    ->orWhere(function ($nested) use ($order): void {
                        $nested
                            ->where('supplier_id', $order->supplier_id)
                            ->where('order_number', $order->order_number);
                    });
            })
            ->first();

        if (! $inboundOrder instanceof InboundOrder) {
            $inboundOrder = InboundOrder::query()->create([
                'company_id' => $order->company_id,
                'supplier_id' => $order->supplier_id,
                'supplier_order_id' => $order->getKey(),
                'order_number' => $order->order_number,
                'supplier_order_reference' => $confirmation->supplier_reference,
                'status' => $this->statusValue($order->status),
                'ordered_at' => $order->order_date,
                'expected_arrival_date' => $confirmation->expected_arrival_date,
                'confirmed_arrival_date' => $confirmation->expected_arrival_date,
                'ready_date' => $confirmation->ready_date,
                'shipped_date' => $confirmation->shipping_date,
            ]);
        }

        $inboundOrder->forceFill([
            'supplier_order_id' => $order->getKey(),
            'supplier_order_reference' => $confirmation->supplier_reference ?? $inboundOrder->supplier_order_reference,
            'status' => $this->statusValue($order->status),
            'expected_arrival_date' => $confirmation->expected_arrival_date ?? $inboundOrder->expected_arrival_date,
            'confirmed_arrival_date' => $confirmation->expected_arrival_date ?? $inboundOrder->confirmed_arrival_date,
            'ready_date' => $confirmation->ready_date ?? $inboundOrder->ready_date,
            'shipped_date' => $confirmation->shipping_date ?? $inboundOrder->shipped_date,
        ])->save();

        $updatedItems = 0;

        foreach ($items as $item) {
            $orderItem = $item['supplier_order_item'] ?? null;

            if ($orderItem === null) {
                continue;
            }

            $inboundItem = $inboundOrder->items->firstWhere('product_id', $orderItem->product_id)
                ?? $inboundOrder->items()->create([
                    'product_id' => $orderItem->product_id,
                    'ordered_quantity' => $orderItem->ordered_quantity,
                    'expected_arrival_date' => $confirmation->expected_arrival_date,
                    'status' => 'created',
                ]);

            $inboundItem->forceFill([
                'ordered_quantity' => $orderItem->ordered_quantity,
                'confirmed_quantity' => $item['confirmed_quantity'],
                'confirmed_arrival_date' => $confirmation->expected_arrival_date ?? $inboundItem->confirmed_arrival_date,
                'status' => $item['item_status'] ?? 'confirmed',
            ])->save();
            $updatedItems++;
        }

        return [
            'inbound_order' => $inboundOrder->refresh(),
            'items_count' => $updatedItems,
        ];
    }

    private function statusValue(mixed $status): string
    {
        return $status instanceof \BackedEnum ? $status->value : (string) $status;
    }
}
