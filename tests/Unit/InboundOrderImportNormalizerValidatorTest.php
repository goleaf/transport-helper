<?php

use App\Models\Company;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\Import\Normalizers\InboundOrderNormalizer;
use App\Services\Import\Validators\InboundOrderValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('normalizes and validates inbound order rows', function () {
    $company = Company::factory()->create();
    $supplier = Supplier::factory()->for($company)->create();
    Product::factory()->for($company)->create(['sku' => 'SKU-1001']);
    $row = app(InboundOrderNormalizer::class)->normalize([
        'po_number' => 'PO-1001',
        'product_sku' => 'sku-1001',
        'qty' => '120',
        'eta' => '2026-07-15',
        'status' => 'ordered',
    ], ['company_id' => $company->getKey(), 'supplier_id' => $supplier->getKey()]);
    $result = app(InboundOrderValidator::class)->validate($row);

    expect($row['order_number'])->toBe('PO-1001')
        ->and($result['valid'])->toBeTrue()
        ->and($result['normalized'])->toHaveKey('product_id');
});

it('fails inbound order validation for unknown sku, invalid date and invalid quantity', function () {
    $company = Company::factory()->create();
    $supplier = Supplier::factory()->for($company)->create();
    $row = app(InboundOrderNormalizer::class)->normalize([
        'order_number' => 'PO-1001',
        'sku' => 'missing',
        'ordered_quantity' => '-1',
        'expected_arrival_date' => 'bad',
        'status' => 'ordered',
    ], ['company_id' => $company->getKey(), 'supplier_id' => $supplier->getKey()]);
    $result = app(InboundOrderValidator::class)->validate($row);

    expect($result['valid'])->toBeFalse()
        ->and(implode(' ', $result['errors']))->toContain('SKU not found');
});
