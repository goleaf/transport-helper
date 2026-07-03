<?php

use App\Models\Company;
use App\Models\Product;
use App\Services\Import\Normalizers\ReservationNormalizer;
use App\Services\Import\Validators\ReservationValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('normalizes and validates reservation rows', function () {
    $company = Company::factory()->create();
    Product::factory()->for($company)->create(['sku' => 'SKU-1001']);
    $row = app(ReservationNormalizer::class)->normalize([
        'product_sku' => 'sku-1001',
        'reserved_qty' => '24',
        'project' => 'Project A',
        'reservation_date' => '2026-07-01',
        'usage_date' => '2026-08-01',
        'status' => 'active',
    ], ['company_id' => $company->getKey()]);
    $result = app(ReservationValidator::class)->validate($row);

    expect($row['quantity'])->toBe(24.0)
        ->and($result['valid'])->toBeTrue()
        ->and($result['normalized'])->toHaveKey('product_id');
});

it('fails reservation validation for unknown sku, invalid date and invalid quantity', function () {
    $company = Company::factory()->create();
    $row = app(ReservationNormalizer::class)->normalize([
        'sku' => 'missing',
        'quantity' => '-1',
        'reserved_at' => 'bad',
        'status' => 'active',
    ], ['company_id' => $company->getKey()]);
    $result = app(ReservationValidator::class)->validate($row);

    expect($result['valid'])->toBeFalse()
        ->and(implode(' ', $result['errors']))->toContain('SKU not found');
});
