<?php

use App\Models\Company;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\Import\Normalizers\ProductRuleNormalizer;
use App\Services\Import\Validators\ProductRuleValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('normalizes and validates product rule rows', function () {
    $company = Company::factory()->create();
    $supplier = Supplier::factory()->for($company)->create();
    Product::factory()->for($company)->create(['sku' => 'SKU-1001']);
    $row = app(ProductRuleNormalizer::class)->normalize([
        'product_sku' => 'sku-1001',
        'supplier_sku' => 'SUP-1001',
        'minimum_order_quantity' => '24',
        'pack_qty' => '12',
        'pallet_qty' => '144',
        'lead_time_days' => '21',
        'safety_days' => '14',
        'order_enabled' => 'yes',
    ], ['company_id' => $company->getKey(), 'supplier_id' => $supplier->getKey()]);
    $result = app(ProductRuleValidator::class)->validate($row);

    expect($row['moq'])->toBe(24.0)
        ->and($row['pack_multiple'])->toBe(12.0)
        ->and($result['valid'])->toBeTrue()
        ->and($result['normalized'])->toHaveKey('product_id');
});

it('fails product rule validation for unknown sku, invalid quantity and missing supplier', function () {
    $company = Company::factory()->create();
    $row = app(ProductRuleNormalizer::class)->normalize([
        'sku' => 'missing',
        'pack_multiple' => '-1',
    ], ['company_id' => $company->getKey()]);
    $result = app(ProductRuleValidator::class)->validate($row);

    expect($result['valid'])->toBeFalse()
        ->and(implode(' ', $result['errors']))->toContain('SKU not found');
});
