<?php

namespace App\Services\Import\Persisters;

use App\Contracts\Import\ImportPersisterInterface;
use App\Models\InboundOrder;
use App\Models\InboundOrderItem;

class InboundOrderPersister implements ImportPersisterInterface
{
    public function persist(array $row, array $context = []): array
    {
        $order = InboundOrder::query()->updateOrCreate(
            [
                'company_id' => $row['company_id'],
                'supplier_id' => $row['supplier_id'],
                'order_number' => $row['order_number'],
            ],
            [
                'supplier_order_reference' => $row['supplier_order_reference'] ?? $row['source_reference'] ?? null,
                'status' => $row['status'] ?? 'ordered',
                'ordered_at' => now(),
                'expected_arrival_date' => $row['expected_arrival_date'] ?? null,
                'confirmed_arrival_date' => $row['confirmed_arrival_date'] ?? null,
                'ready_date' => $row['ready_date'] ?? null,
                'shipped_date' => $row['shipped_date'] ?? null,
                'notes' => $row['notes'] ?? null,
            ],
        );

        $item = InboundOrderItem::query()->updateOrCreate(
            [
                'inbound_order_id' => $order->getKey(),
                'product_id' => $row['product_id'],
            ],
            [
                'ordered_quantity' => $row['ordered_quantity'],
                'confirmed_quantity' => $row['confirmed_quantity'] ?? null,
                'received_quantity' => null,
                'expected_arrival_date' => $row['expected_arrival_date'] ?? null,
                'confirmed_arrival_date' => $row['confirmed_arrival_date'] ?? null,
                'status' => $row['status'] ?? 'ordered',
            ],
        );

        return [
            'model_type' => InboundOrderItem::class,
            'model_id' => (int) $item->getKey(),
            'model' => $item,
            'metadata' => [
                'inbound_order_id' => (int) $order->getKey(),
            ],
        ];
    }
}
