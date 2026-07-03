<?php

use App\Models\Product;
use App\Models\SupplierOrderItem;
use App\Services\Supply\Confirmations\SupplierConfirmationItemMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\SupplierConfirmationTestSupport;

uses(RefreshDatabase::class);

it('matches by product sku', function () {
    $fixture = SupplierConfirmationTestSupport::fixture();

    $match = app(SupplierConfirmationItemMatcher::class)->match($fixture['supplierOrder'], ['sku' => 'AX-150']);

    expect($match['matched'])->toBeTrue()
        ->and($match['matched_by'])->toBe('sku')
        ->and($match['supplier_order_item']->is($fixture['supplierOrderItem']))->toBeTrue();
});

it('matches by manufacturer sku', function () {
    $fixture = SupplierConfirmationTestSupport::fixture();

    $match = app(SupplierConfirmationItemMatcher::class)->match($fixture['supplierOrder'], ['manufacturer_sku' => 'MFG-AX-150']);

    expect($match['matched'])->toBeTrue()
        ->and($match['matched_by'])->toBe('manufacturer_sku');
});

it('matches by supplier sku', function () {
    $fixture = SupplierConfirmationTestSupport::fixture();

    $match = app(SupplierConfirmationItemMatcher::class)->match($fixture['supplierOrder'], ['supplier_sku' => 'SUP-AX-150']);

    expect($match['matched'])->toBeTrue()
        ->and($match['matched_by'])->toBe('supplier_sku');
});

it('matches by product id', function () {
    $fixture = SupplierConfirmationTestSupport::fixture();

    $match = app(SupplierConfirmationItemMatcher::class)->match($fixture['supplierOrder'], ['product_id' => $fixture['product']->getKey()]);

    expect($match['matched'])->toBeTrue()
        ->and($match['matched_by'])->toBe('product_id');
});

it('unknown sku returns no match', function () {
    $fixture = SupplierConfirmationTestSupport::fixture();

    $match = app(SupplierConfirmationItemMatcher::class)->match($fixture['supplierOrder'], ['sku' => 'UNKNOWN']);

    expect($match['matched'])->toBeFalse()
        ->and($match['warnings'])->toContain('unknown_sku');
});

it('ambiguous sku returns ambiguous', function () {
    $fixture = SupplierConfirmationTestSupport::fixture();
    $duplicateProduct = Product::factory()->for($fixture['company'])->create([
        'sku' => 'AX-151',
        'manufacturer_sku' => 'MFG-AX-150',
    ]);
    SupplierOrderItem::factory()->create([
        'supplier_order_id' => $fixture['supplierOrder']->getKey(),
        'product_id' => $duplicateProduct->getKey(),
        'ordered_quantity' => 10,
    ]);

    $match = app(SupplierConfirmationItemMatcher::class)->match($fixture['supplierOrder']->fresh(), ['manufacturer_sku' => 'MFG-AX-150']);

    expect($match['matched'])->toBeFalse()
        ->and($match['ambiguous'])->toBeTrue()
        ->and($match['warnings'])->toContain('ambiguous_sku');
});

it('does not fuzzy auto match', function () {
    $fixture = SupplierConfirmationTestSupport::fixture();

    $match = app(SupplierConfirmationItemMatcher::class)->match($fixture['supplierOrder'], [
        'product_name' => 'Axle Bearing 150',
        'sku' => 'AX 150',
    ]);

    expect($match['matched'])->toBeFalse();
});
