<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\InboundOrder;
use App\Models\InboundOrderItem;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\SalesHistory;
use App\Models\StockSnapshot;
use App\Models\Supplier;
use App\Models\SupplierProductRule;
use Illuminate\Database\Seeder;

class DemoProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company = Company::query()->firstOrCreate(
            ['code' => 'DEMO'],
            [
                'name' => 'Demo Supply Company',
                'timezone' => 'Europe/Vilnius',
                'default_currency' => 'EUR',
            ]
        );

        $supplier = Supplier::query()->firstOrCreate(
            [
                'company_id' => $company->getKey(),
                'code' => 'DEMO-MANUFACTURER',
            ],
            [
                'name' => 'Demo Manufacturer',
                'type' => 'manufacturer',
                'default_language' => 'en',
                'default_currency' => 'EUR',
                'default_lead_time_days' => 21,
                'is_active' => true,
            ]
        );

        $products = [
            ['SKU-1001', 'Demo Product 1001', 'MFG-1001', 'Core Parts', 'Demo Brand'],
            ['SKU-1002', 'Demo Product 1002', 'MFG-1002', 'Core Parts', 'Demo Brand'],
            ['SKU-1003', 'Demo Product 1003', 'MFG-1003', 'Accessories', 'Demo Brand'],
            ['SKU-1004', 'Demo Product 1004', 'MFG-1004', 'Accessories', 'Demo Brand'],
            ['SKU-1005', 'Demo Product 1005', 'MFG-1005', 'Service Parts', 'Demo Brand'],
        ];

        foreach ($products as $index => [$sku, $name, $manufacturerSku, $category, $brand]) {
            $product = Product::query()->updateOrCreate(
                [
                    'company_id' => $company->getKey(),
                    'sku' => $sku,
                ],
                [
                    'manufacturer_sku' => $manufacturerSku,
                    'name' => $name,
                    'category' => $category,
                    'brand' => $brand,
                    'unit' => 'pcs',
                    'is_active' => true,
                ]
            );

            SupplierProductRule::query()->updateOrCreate(
                [
                    'supplier_id' => $supplier->getKey(),
                    'product_id' => $product->getKey(),
                ],
                [
                    'supplier_sku' => 'SUP-'.$sku,
                    'moq' => $index === 0 ? 24 : 0,
                    'pack_multiple' => 12,
                    'pallet_quantity' => 144,
                    'min_transport_quantity' => 144,
                    'lead_time_days' => 21,
                    'safety_days' => 14,
                    'safety_rule_type' => 'days',
                    'transport_rule_type' => 'standard',
                    'order_enabled' => true,
                ]
            );

            StockSnapshot::query()->updateOrCreate(
                [
                    'company_id' => $company->getKey(),
                    'product_id' => $product->getKey(),
                    'snapshot_date' => now()->toDateString(),
                ],
                [
                    'free_stock' => 70 + ($index * 10),
                    'total_stock' => 90 + ($index * 10),
                    'reserved_quantity' => $index === 0 ? 12 : 0,
                    'damaged_quantity' => 0,
                    'inactive_quantity' => 0,
                    'in_transit_quantity' => 20,
                    'source_type' => 'demo',
                    'source_reference' => 'demo-stock-'.$sku,
                ]
            );

            foreach ([1, 8, 15] as $dayOffset) {
                SalesHistory::query()->updateOrCreate(
                    [
                        'company_id' => $company->getKey(),
                        'product_id' => $product->getKey(),
                        'sales_date' => now()->subYear()->subDays($dayOffset)->toDateString(),
                        'channel' => 'demo',
                    ],
                    [
                        'quantity' => 10 + $index,
                        'is_promotion' => false,
                        'is_anomaly' => false,
                        'source_type' => 'demo',
                        'source_reference' => 'demo-ly-'.$sku.'-'.$dayOffset,
                    ]
                );

                SalesHistory::query()->updateOrCreate(
                    [
                        'company_id' => $company->getKey(),
                        'product_id' => $product->getKey(),
                        'sales_date' => now()->subDays($dayOffset)->toDateString(),
                        'channel' => 'demo',
                    ],
                    [
                        'quantity' => 12 + $index,
                        'is_promotion' => false,
                        'is_anomaly' => false,
                        'source_type' => 'demo',
                        'source_reference' => 'demo-cy-'.$sku.'-'.$dayOffset,
                    ]
                );
            }

            if ($index === 0) {
                Reservation::query()->updateOrCreate(
                    [
                        'company_id' => $company->getKey(),
                        'product_id' => $product->getKey(),
                        'source_reference' => 'demo-reservation-'.$sku,
                    ],
                    [
                        'quantity' => 12,
                        'project_name' => 'Demo Project',
                        'customer_name' => 'Demo Customer',
                        'manager_name' => 'Demo Manager',
                        'reserved_at' => now()->toDateString(),
                        'expected_usage_date' => now()->addDays(30)->toDateString(),
                        'status' => 'active',
                        'source_type' => 'demo',
                    ]
                );
            }
        }

        $inboundOrder = InboundOrder::query()->updateOrCreate(
            [
                'company_id' => $company->getKey(),
                'supplier_id' => $supplier->getKey(),
                'order_number' => 'DEMO-INBOUND-1001',
            ],
            [
                'supplier_order_reference' => 'SUP-DEMO-INBOUND-1001',
                'status' => 'open',
                'ordered_at' => now()->subDays(7),
                'expected_arrival_date' => now()->addDays(14)->toDateString(),
                'confirmed_arrival_date' => null,
                'ready_date' => now()->addDays(10)->toDateString(),
                'shipped_date' => null,
                'notes' => 'Demo inbound order for stock projection.',
            ]
        );

        $firstProduct = Product::query()
            ->where('company_id', $company->getKey())
            ->where('sku', 'SKU-1001')
            ->first();

        if ($firstProduct instanceof Product) {
            InboundOrderItem::query()->updateOrCreate(
                [
                    'inbound_order_id' => $inboundOrder->getKey(),
                    'product_id' => $firstProduct->getKey(),
                ],
                [
                    'ordered_quantity' => 144,
                    'confirmed_quantity' => 144,
                    'received_quantity' => null,
                    'expected_arrival_date' => now()->addDays(14)->toDateString(),
                    'confirmed_arrival_date' => null,
                    'status' => 'open',
                ]
            );
        }
    }
}
