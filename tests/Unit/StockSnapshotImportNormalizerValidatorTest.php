<?php

use App\Models\Company;
use App\Models\Product;
use App\Services\Import\Normalizers\StockSnapshotNormalizer;
use App\Services\Import\Validators\StockSnapshotValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('normalizes and validates stock snapshot rows', function () {
    $company = Company::factory()->create();
    Product::factory()->for($company)->create(['sku' => 'SKU-1001']);
    $normalizer = app(StockSnapshotNormalizer::class);
    $validator = app(StockSnapshotValidator::class);

    $row = $normalizer->normalize([
        'product_sku' => 'sku-1001',
        'stock_date' => '2026-07-01',
        'available_qty' => '70',
        'total_qty' => '100',
    ], ['company_id' => $company->getKey()]);
    $result = $validator->validate($row);

    expect($row['free_stock'])->toBe(70.0)
        ->and($result['valid'])->toBeTrue()
        ->and($result['normalized'])->toHaveKey('product_id');
});

it('fails stock snapshot validation for unknown sku, invalid date and invalid quantity', function () {
    $company = Company::factory()->create();
    $row = app(StockSnapshotNormalizer::class)->normalize([
        'sku' => 'missing',
        'snapshot_date' => 'bad',
        'free_stock' => 'bad',
    ], ['company_id' => $company->getKey()]);
    $result = app(StockSnapshotValidator::class)->validate($row);

    expect($result['valid'])->toBeFalse()
        ->and(implode(' ', $result['errors']))->toContain('SKU not found');
});
