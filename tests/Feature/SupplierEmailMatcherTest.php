<?php

use App\Models\Company;
use App\Models\Supplier;
use App\Models\SupplierContact;
use App\Services\Email\SupplierEmailMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('matches supplier by exact contact email', function () {
    $company = Company::factory()->create();
    $supplier = Supplier::factory()->for($company)->create();
    SupplierContact::factory()->for($supplier)->create(['email' => 'orders@supplier.test']);

    $match = app(SupplierEmailMatcher::class)->match($company, 'orders@supplier.test');

    expect($match['supplier_id'])->toBe($supplier->id)
        ->and($match['method'])->toBe('exact_contact_email');
});

it('matches supplier by unique domain', function () {
    $company = Company::factory()->create();
    $supplier = Supplier::factory()->for($company)->create();
    SupplierContact::factory()->for($supplier)->create(['email' => 'orders@supplier.test']);

    $match = app(SupplierEmailMatcher::class)->match($company, 'reply@supplier.test');

    expect($match['supplier_id'])->toBe($supplier->id)
        ->and($match['warnings'])->toContain('supplier_matched_by_domain');
});

it('domain ambiguous returns no match', function () {
    $company = Company::factory()->create();
    $first = Supplier::factory()->for($company)->create();
    $second = Supplier::factory()->for($company)->create();
    SupplierContact::factory()->for($first)->create(['email' => 'orders@supplier.test']);
    SupplierContact::factory()->for($second)->create(['email' => 'sales@supplier.test']);

    $match = app(SupplierEmailMatcher::class)->match($company, 'reply@supplier.test');

    expect($match['supplier_id'])->toBeNull()
        ->and($match['warnings'])->toContain('supplier_domain_ambiguous');
});

it('unknown supplier returns warning', function () {
    $company = Company::factory()->create();

    $match = app(SupplierEmailMatcher::class)->match($company, 'unknown@example.test');

    expect($match['supplier_id'])->toBeNull()
        ->and($match['warnings'])->toContain('unknown_supplier');
});
