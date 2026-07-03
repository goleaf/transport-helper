<?php

use App\Enums\LogisticsStatus;
use App\Enums\SupplierOrderStatus;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\LogisticsRecord;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierContact;
use App\Models\SupplierOrder;
use App\Models\SupplierOrderItem;
use App\Models\SupplierProductRule;
use App\Models\User;

function stage5SupplierOrderFixture(array $orderOverrides = [], array $itemOverrides = [], array $supplierOverrides = []): array
{
    $company = Company::factory()->create(['name' => 'Demo Supply Co', 'code' => 'DEMO']);
    $supplier = Supplier::factory()->for($company)->create(array_merge([
        'name' => 'Acme Manufacturing',
        'code' => 'ACME',
        'default_language' => 'en',
        'default_currency' => 'EUR',
    ], $supplierOverrides));
    $product = Product::factory()->for($company)->create([
        'sku' => 'AX-150',
        'manufacturer_sku' => 'M-AX-150',
        'name' => 'Axle Bearing 150',
        'unit' => 'pcs',
    ]);

    SupplierProductRule::factory()->create([
        'supplier_id' => $supplier->id,
        'product_id' => $product->id,
        'supplier_sku' => 'SUP-AX-150',
    ]);

    $order = SupplierOrder::factory()->create(array_merge([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'order_proposal_id' => null,
        'order_number' => 'PO-TEST-1',
        'status' => SupplierOrderStatus::Draft,
        'order_date' => '2026-07-03',
    ], $orderOverrides));

    $item = SupplierOrderItem::factory()->create(array_merge([
        'supplier_order_id' => $order->id,
        'product_id' => $product->id,
        'ordered_quantity' => 156,
        'unit_price' => 12.5,
        'currency' => 'EUR',
        'status' => 'draft',
        'notes' => 'Stage 5 test line',
    ], $itemOverrides));

    $contact = SupplierContact::factory()->for($supplier)->create([
        'name' => 'Orders Desk',
        'email' => 'orders@example.test',
        'receives_orders' => true,
        'is_active' => true,
    ]);

    $user = User::factory()->create(['role' => UserRole::SupplyManager]);
    $viewer = User::factory()->create(['role' => UserRole::Viewer]);
    $logisticsRecord = LogisticsRecord::factory()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'supplier_order_id' => $order->id,
        'status' => LogisticsStatus::Planned,
    ]);

    return compact('company', 'supplier', 'product', 'order', 'item', 'contact', 'user', 'viewer', 'logisticsRecord');
}
