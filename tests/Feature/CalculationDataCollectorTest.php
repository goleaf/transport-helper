<?php

use App\Models\Company;
use App\Models\InboundOrder;
use App\Models\InboundOrderItem;
use App\Models\Product;
use App\Models\SalesHistory;
use App\Models\StockSnapshot;
use App\Models\Supplier;
use App\Models\SupplierProductRule;
use App\Services\Supply\Calculation\CalculationDataCollector;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function stageTwoCollectorParameters(array $overrides = []): array
{
    return array_merge([
        't0_date' => '2026-07-01',
        't1_date' => '2026-07-15',
        't2_date' => '2026-08-14',
        't3_date' => '2026-09-01',
        'trend_current_start' => '2026-04-01',
        'trend_current_end' => '2026-07-01',
        'trend_last_start' => '2025-04-01',
        'trend_last_end' => '2025-07-01',
        'last_year_t0_t1_start' => '2025-07-01',
        'last_year_t0_t1_end' => '2025-07-15',
        'last_year_t1_t2_start' => '2025-07-15',
        'last_year_t1_t2_end' => '2025-08-14',
        'last_year_t2_t3_start' => '2025-08-14',
        'last_year_t2_t3_end' => '2025-09-01',
        'reservation_strategy' => 'reserved_not_removed_from_free_stock',
        'rounding_strategy' => [
            'pallet' => 'show_only',
            'transport' => 'show_only',
        ],
        'strategic_minimum_order_enabled' => false,
    ], $overrides);
}

it('collects calculation data for a product', function () {
    $company = Company::factory()->create();
    $supplier = Supplier::factory()->for($company)->create();
    $product = Product::factory()->for($company)->create();

    SupplierProductRule::factory()
        ->for($supplier)
        ->for($product)
        ->create([
            'moq' => null,
            'pack_multiple' => 12,
            'pallet_quantity' => 144,
            'min_transport_quantity' => 144,
            'lead_time_days' => 21,
            'safety_days' => 14,
        ]);

    StockSnapshot::factory()->for($company)->for($product)->create([
        'snapshot_date' => '2026-07-01',
        'free_stock' => 70,
    ]);

    foreach ([
        ['2026-06-01', 120],
        ['2025-06-01', 100],
        ['2025-07-10', 40],
        ['2025-07-20', 100],
        ['2025-08-20', 60],
    ] as [$date, $quantity]) {
        SalesHistory::factory()->for($company)->for($product)->create([
            'sales_date' => $date,
            'quantity' => $quantity,
        ]);
    }

    $inboundOrder = InboundOrder::factory()->for($company)->for($supplier)->create([
        'status' => 'open',
        'expected_arrival_date' => '2026-08-01',
    ]);

    InboundOrderItem::factory()->for($inboundOrder)->for($product)->create([
        'ordered_quantity' => 20,
        'confirmed_quantity' => null,
        'expected_arrival_date' => '2026-08-01',
        'status' => 'open',
    ]);

    $collected = app(CalculationDataCollector::class)->collectForProduct(
        $company,
        $supplier,
        $product,
        stageTwoCollectorParameters(),
    );

    expect($collected['input'])->toMatchArray([
        'free_stock' => '70.000',
        'current_year_sales_for_trend' => 120.0,
        'last_year_sales_for_trend' => 100.0,
        'last_year_sales_t0_t1' => 40.0,
        'last_year_sales_t1_t2' => 100.0,
        'last_year_sales_t2_t3' => 60.0,
        'inbound_t1_t3' => 20.0,
        'pack_multiple' => '12.000',
    ]);
});

it('adds a warning when stock snapshot is missing', function () {
    $company = Company::factory()->create();
    $supplier = Supplier::factory()->for($company)->create();
    $product = Product::factory()->for($company)->create();

    SupplierProductRule::factory()->for($supplier)->for($product)->create();

    $collected = app(CalculationDataCollector::class)->collectForProduct(
        $company,
        $supplier,
        $product,
        stageTwoCollectorParameters(),
    );

    expect($collected['warnings'])->toContain('missing_stock_snapshot');
});

it('adds a warning when supplier product rule is missing', function () {
    $company = Company::factory()->create();
    $supplier = Supplier::factory()->for($company)->create();
    $product = Product::factory()->for($company)->create();

    StockSnapshot::factory()->for($company)->for($product)->create([
        'snapshot_date' => '2026-07-01',
        'free_stock' => 70,
    ]);

    $collected = app(CalculationDataCollector::class)->collectForProduct(
        $company,
        $supplier,
        $product,
        stageTwoCollectorParameters(),
    );

    expect($collected['warnings'])->toContain('missing_supplier_product_rule');
});
