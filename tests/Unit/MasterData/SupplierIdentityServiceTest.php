<?php

use App\Models\AuditLog;
use App\Models\Supplier;
use App\Models\SupplierAlias;
use App\Models\SupplierContact;
use App\Services\Supply\MasterData\SupplierIdentityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MasterDataTestSupport;

uses(RefreshDatabase::class);

it('resolves suppliers by id code contact email alias and unique domain', function (): void {
    $fixture = MasterDataTestSupport::fixture();
    SupplierAlias::factory()->for($fixture['company'])->for($fixture['supplier'])->create(['alias' => 'Nordic Old Name', 'status' => 'active']);

    $service = app(SupplierIdentityService::class);

    expect($service->resolve($fixture['company'], ['supplier_id' => $fixture['supplier']->id])['matched_by'])->toBe('supplier_id')
        ->and($service->resolve($fixture['company'], ['code' => 'NORDIC'])['matched_by'])->toBe('supplier_code')
        ->and($service->resolve($fixture['company'], ['from_email' => 'orders@nordic.test'])['matched_by'])->toBe('contact_email')
        ->and($service->resolve($fixture['company'], ['alias' => 'Nordic Old Name'])['matched_by'])->toBe('supplier_alias')
        ->and($service->resolve($fixture['company'], ['domain' => 'nordic.test'])['matched_by'])->toBe('unique_email_domain');
});

it('does not resolve ambiguous domain and keeps fuzzy supplier names suggestions only', function (): void {
    $fixture = MasterDataTestSupport::fixture();
    $other = Supplier::factory()->for($fixture['company'])->create(['name' => 'Nordic Parts LLC']);
    SupplierContact::factory()->for($other)->create(['email' => 'sales@nordic.test']);

    $service = app(SupplierIdentityService::class);
    $domain = $service->resolve($fixture['company'], ['domain' => 'nordic.test']);
    $fuzzy = $service->resolve($fixture['company'], ['name' => 'Nordik Partz']);

    expect($domain['matched'])->toBeFalse()
        ->and($domain['warnings'])->toContain('ambiguous_supplier_email_domain')
        ->and($fuzzy['matched'])->toBeFalse()
        ->and($fuzzy['suggestions'])->not->toBeEmpty();
});

it('approves and rejects supplier aliases with audit events', function (): void {
    $fixture = MasterDataTestSupport::fixture();
    $service = app(SupplierIdentityService::class);
    $alias = SupplierAlias::factory()->for($fixture['company'])->for($fixture['supplier'])->create([
        'alias' => 'Pending Supplier',
        'status' => 'pending',
    ]);
    $rejected = SupplierAlias::factory()->for($fixture['company'])->for($fixture['supplier'])->create([
        'alias' => 'Rejected Supplier',
        'status' => 'pending',
    ]);

    $service->approveAlias($alias, $fixture['admin'], 'Reviewed supplier.');
    $service->rejectAlias($rejected, $fixture['admin'], 'Wrong supplier.');

    expect($alias->refresh()->status->value)->toBe('active')
        ->and($rejected->refresh()->status->value)->toBe('rejected')
        ->and(AuditLog::query()->forEvent('supplier_alias_approved')->exists())->toBeTrue()
        ->and(AuditLog::query()->forEvent('supplier_alias_rejected')->exists())->toBeTrue();
});
