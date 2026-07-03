<?php

use App\Services\Supply\Confirmations\SupplierConfirmationDiscrepancyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\SupplierConfirmationTestSupport;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function detectSupplierConfirmationDiscrepancies(array $fixture, array $matchedItems, array $normalized = []): array
{
    return app(SupplierConfirmationDiscrepancyService::class)->detect($fixture['supplierOrder'], $matchedItems, array_replace_recursive(SupplierConfirmationTestSupport::manualData(), $normalized));
}

it('has no discrepancy when quantities match', function () {
    $fixture = SupplierConfirmationTestSupport::fixture();

    $result = detectSupplierConfirmationDiscrepancies($fixture, [[
        'matched' => true,
        'supplier_order_item' => $fixture['supplierOrderItem'],
        'confirmed_quantity' => 156.0,
        'source_item' => ['sku' => 'AX-150'],
    ]]);

    expect($result['has_discrepancies'])->toBeFalse();
});

it('lower quantity creates quantity lower discrepancy', function () {
    $fixture = SupplierConfirmationTestSupport::fixture();

    $result = detectSupplierConfirmationDiscrepancies($fixture, [[
        'matched' => true,
        'supplier_order_item' => $fixture['supplierOrderItem'],
        'confirmed_quantity' => 120.0,
        'source_item' => ['sku' => 'AX-150'],
    ]]);

    expect(collect($result['discrepancies'])->pluck('type'))->toContain('quantity_lower_than_ordered')
        ->and($result['blocking'])->toBeFalse();
});

it('higher quantity creates blocking quantity higher discrepancy', function () {
    $fixture = SupplierConfirmationTestSupport::fixture();

    $result = detectSupplierConfirmationDiscrepancies($fixture, [[
        'matched' => true,
        'supplier_order_item' => $fixture['supplierOrderItem'],
        'confirmed_quantity' => 200.0,
        'source_item' => ['sku' => 'AX-150'],
    ]]);

    expect(collect($result['discrepancies'])->pluck('type'))->toContain('quantity_higher_than_ordered')
        ->and($result['blocking'])->toBeTrue();
});

it('missing confirmed quantity creates discrepancy', function () {
    $fixture = SupplierConfirmationTestSupport::fixture();

    $result = detectSupplierConfirmationDiscrepancies($fixture, [[
        'matched' => true,
        'supplier_order_item' => $fixture['supplierOrderItem'],
        'confirmed_quantity' => null,
        'source_item' => ['sku' => 'AX-150'],
    ]]);

    expect(collect($result['discrepancies'])->pluck('type'))->toContain('missing_confirmed_quantity');
});

it('missing ordered item creates missing item', function () {
    $fixture = SupplierConfirmationTestSupport::fixture(withSecondItem: true);

    $result = detectSupplierConfirmationDiscrepancies($fixture, [[
        'matched' => true,
        'supplier_order_item' => $fixture['supplierOrderItem'],
        'confirmed_quantity' => 156.0,
        'source_item' => ['sku' => 'AX-150'],
    ]]);

    expect(collect($result['discrepancies'])->pluck('type'))->toContain('missing_item');
});

it('unknown sku creates blocking discrepancy', function () {
    $fixture = SupplierConfirmationTestSupport::fixture();

    $result = detectSupplierConfirmationDiscrepancies($fixture, [[
        'matched' => false,
        'ambiguous' => false,
        'supplier_order_item' => null,
        'source_item' => ['sku' => 'UNKNOWN', 'confirmed_quantity' => 1],
    ]]);

    expect(collect($result['discrepancies'])->pluck('type'))->toContain('unknown_sku')
        ->and($result['blocking'])->toBeTrue();
});

it('ready date delay creates delayed ready date', function () {
    $fixture = SupplierConfirmationTestSupport::fixture();

    $result = detectSupplierConfirmationDiscrepancies($fixture, [[
        'matched' => true,
        'supplier_order_item' => $fixture['supplierOrderItem'],
        'confirmed_quantity' => 156.0,
        'source_item' => ['sku' => 'AX-150'],
    ]], ['ready_date' => '2026-07-12']);

    expect(collect($result['discrepancies'])->pluck('type'))->toContain('delayed_ready_date');
});

it('shipping before ready creates date conflict', function () {
    $fixture = SupplierConfirmationTestSupport::fixture();

    $result = detectSupplierConfirmationDiscrepancies($fixture, [[
        'matched' => true,
        'supplier_order_item' => $fixture['supplierOrderItem'],
        'confirmed_quantity' => 156.0,
        'source_item' => ['sku' => 'AX-150'],
    ]], [
        'ready_date' => '2026-07-12',
        'shipping_date' => '2026-07-10',
    ]);

    expect(collect($result['discrepancies'])->pluck('type'))->toContain('date_conflict')
        ->and($result['summary'])->toContain('date');
});
