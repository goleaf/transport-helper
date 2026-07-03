<?php

use App\Models\Company;
use App\Models\Product;
use App\Services\Import\Normalizers\SalesHistoryNormalizer;
use App\Services\Import\Validators\SalesHistoryValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('normalizes and validates sales history rows', function () {
    $company = Company::factory()->create();
    Product::factory()->for($company)->create(['sku' => 'SKU-1001']);
    $normalizer = app(SalesHistoryNormalizer::class);
    $validator = app(SalesHistoryValidator::class);

    $row = $normalizer->normalize([
        'Product SKU' => 'ignored',
        'product_sku' => 'sku-1001',
        'date' => '01.07.2026',
        'sales_qty' => '12,5',
        'promo' => 'yes',
    ], ['company_id' => $company->getKey(), 'source_type' => 'csv']);
    $result = $validator->validate($row);

    expect($row['sku'])->toBe('SKU-1001')
        ->and($row['sales_date'])->toBe('2026-07-01')
        ->and($row['quantity'])->toBe(12.5)
        ->and($result['valid'])->toBeTrue()
        ->and($result['normalized'])->toHaveKey('product_id');
});

it('fails sales history validation for unknown sku, invalid date and invalid quantity', function () {
    $company = Company::factory()->create();
    $normalizer = app(SalesHistoryNormalizer::class);
    $validator = app(SalesHistoryValidator::class);

    $row = $normalizer->normalize([
        'sku' => 'missing',
        'sales_date' => 'bad',
        'quantity' => '-1',
    ], ['company_id' => $company->getKey()]);
    $result = $validator->validate($row);

    expect($result['valid'])->toBeFalse()
        ->and(implode(' ', $result['errors']))->toContain('SKU not found');
});
