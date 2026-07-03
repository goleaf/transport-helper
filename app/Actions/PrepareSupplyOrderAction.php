<?php

namespace App\Actions;

use App\Enums\SupplyOrderStatus;
use App\Models\Product;
use App\Models\SupplyOrder;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PrepareSupplyOrderAction
{
    public function __construct(
        public CalculateSupplyOrderQuantitiesAction $calculateSupplyOrderQuantities,
        public RecordSupplyAuditAction $recordSupplyAudit,
    ) {}

    public function handle(Product $product, int $requestedQuantity, ?string $customerReference = null, ?User $actor = null): SupplyOrder
    {
        $product->loadMissing(['manufacturer', 'stockItem']);

        if ($product->manufacturer === null) {
            throw new DomainException('A product must have a manufacturer before a supply order can be prepared.');
        }

        $stockItem = $product->stockItem;
        $quantities = $this->calculateSupplyOrderQuantities->handle(
            requestedQuantity: $requestedQuantity,
            availableQuantity: $stockItem?->available_quantity ?? 0,
            incomingQuantity: $stockItem?->incoming_quantity ?? 0,
            reservedQuantity: $stockItem?->reserved_quantity ?? 0,
        );

        return DB::transaction(function () use ($actor, $customerReference, $product, $quantities): SupplyOrder {
            $order = SupplyOrder::query()->create([
                'order_number' => $this->makeOrderNumber(),
                'manufacturer_id' => $product->manufacturer_id,
                'product_id' => $product->getKey(),
                'created_by_id' => $actor?->getKey(),
                'status' => SupplyOrderStatus::Draft,
                'customer_reference' => $customerReference,
                'requested_quantity' => $quantities['t0_requested_quantity'],
                'available_quantity' => $quantities['t1_available_quantity'],
                'required_quantity' => $quantities['t2_required_quantity'],
                'manufacturer_quantity' => $quantities['t3_manufacturer_quantity'],
                'reserve_percent' => $quantities['reserve_percent'],
            ]);

            $this->recordSupplyAudit->handle($actor, 'supply_order.prepared', $order, [
                'product_id' => $product->getKey(),
                'customer_reference' => $customerReference,
                'calculation' => $quantities,
            ]);

            return $order->load(['manufacturer', 'product']);
        });
    }

    private function makeOrderNumber(): string
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $orderNumber = 'SO-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));

            if (! SupplyOrder::query()->where('order_number', $orderNumber)->exists()) {
                return $orderNumber;
            }
        }

        throw new DomainException('Unable to generate a unique supply order number.');
    }
}
