<?php

namespace Tests\Support;

use App\Enums\LogisticsStatus;
use App\Enums\SupplierOrderStatus;
use App\Enums\UserRole;
use App\Models\Carrier;
use App\Models\Company;
use App\Models\InboundOrder;
use App\Models\InboundOrderItem;
use App\Models\LogisticsRecord;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierConfirmation;
use App\Models\SupplierOrder;
use App\Models\SupplierOrderItem;
use App\Models\User;

class LogisticsTestSupport
{
    /**
     * @return array<string, mixed>
     */
    public static function fixture(array $overrides = []): array
    {
        $company = Company::factory()->create(['name' => 'Logistics Demo Co', 'default_currency' => 'EUR']);
        $supplier = Supplier::factory()->for($company)->create([
            'name' => 'Demo Manufacturer',
            'type' => 'manufacturer',
            'default_currency' => 'EUR',
        ]);
        $product = Product::factory()->for($company)->create([
            'sku' => 'SKU-1001',
            'manufacturer_sku' => 'M-1001',
            'name' => 'Demo Product',
        ]);
        $supplierOrder = SupplierOrder::factory()->for($company)->for($supplier)->create(array_replace([
            'order_number' => 'PO-LOG-1001',
            'status' => SupplierOrderStatus::Confirmed,
            'order_date' => '2026-07-03',
            'sent_at' => now()->subDays(7),
        ], $overrides['supplier_order'] ?? []));
        $supplierOrderItem = SupplierOrderItem::factory()->for($supplierOrder)->for($product)->create(array_replace([
            'ordered_quantity' => 156,
            'confirmed_quantity' => 156,
            'received_quantity' => null,
            'damaged_quantity' => null,
            'status' => 'confirmed',
        ], $overrides['supplier_order_item'] ?? []));
        $inboundOrder = InboundOrder::factory()->for($company)->for($supplier)->create([
            'supplier_order_id' => $supplierOrder->id,
            'order_number' => $supplierOrder->order_number,
            'status' => 'open',
        ]);
        $inboundOrderItem = InboundOrderItem::factory()->for($inboundOrder)->for($product)->create([
            'ordered_quantity' => 156,
            'confirmed_quantity' => 156,
            'received_quantity' => null,
            'status' => 'open',
        ]);
        $carrier = Carrier::factory()->for($company)->create(['name' => 'Demo Carrier']);
        $confirmation = SupplierConfirmation::factory()->for($company)->for($supplierOrder)->create(array_replace([
            'email_message_id' => null,
            'confirmation_date' => '2026-07-04',
            'ready_date' => '2026-07-10',
            'expected_arrival_date' => '2026-07-20',
            'status' => 'confirmed',
        ], $overrides['confirmation'] ?? []));
        $logisticsRecord = LogisticsRecord::factory()->for($company)->for($supplier)->create(array_replace([
            'supplier_order_id' => $supplierOrder->id,
            'supplier_confirmation_id' => $confirmation->id,
            'carrier_id' => $carrier->id,
            'order_date' => '2026-07-03',
            'confirmation_date' => '2026-07-04',
            'ready_date' => '2026-07-10',
            'pickup_date' => '2026-07-12',
            'delivery_date' => '2026-07-20',
            'actual_received_date' => null,
            'status' => LogisticsStatus::Confirmed,
            'transport_price' => 500,
            'currency' => 'EUR',
        ], $overrides['logistics_record'] ?? []));
        $user = User::factory()->create(['role' => $overrides['role'] ?? UserRole::Admin]);

        return compact(
            'company',
            'supplier',
            'product',
            'supplierOrder',
            'supplierOrderItem',
            'inboundOrder',
            'inboundOrderItem',
            'carrier',
            'confirmation',
            'logisticsRecord',
            'user',
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function receiptPayload(array $fixture, array $overrides = []): array
    {
        return array_replace_recursive([
            'actual_received_date' => '2026-07-21',
            'items' => [
                [
                    'product_id' => $fixture['product']->id,
                    'sku' => $fixture['product']->sku,
                    'received_quantity' => 156,
                    'damaged_quantity' => 0,
                    'notes' => 'Received in full.',
                ],
            ],
            'confirm_mismatches' => false,
            'complete_order' => true,
            'notes' => 'Warehouse receipt.',
        ], $overrides);
    }
}
