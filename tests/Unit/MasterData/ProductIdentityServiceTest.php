<?php

use App\Models\AuditLog;
use App\Models\ProductAlias;
use App\Services\Supply\MasterData\ProductIdentityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MasterDataTestSupport;

uses(RefreshDatabase::class);

it('resolves products by id sku manufacturer alias and supplier sku while pending alias is not final', function (): void {
    $fixture = MasterDataTestSupport::fixture();
    $service = app(ProductIdentityService::class);

    expect($service->resolve($fixture['company'], ['product_id' => $fixture['product']->id])['matched_by'])->toBe('product_id')
        ->and($service->resolve($fixture['company'], ['sku' => 'sku-1001'])['matched_by'])->toBe('sku')
        ->and($service->resolve($fixture['company'], ['manufacturer_sku' => 'mfg-1001'])['matched_by'])->toBe('manufacturer_sku')
        ->and($service->resolve($fixture['company'], ['supplier_sku' => 'SUP-1001'], $fixture['supplier'])['matched_by'])->toBe('supplier_product_rule_supplier_sku');

    ProductAlias::factory()->for($fixture['company'])->for($fixture['product'])->create(['alias' => 'ALT-1001', 'status' => 'active']);
    ProductAlias::factory()->for($fixture['company'])->for($fixture['product'])->create(['alias' => 'PENDING-1001', 'status' => 'pending']);

    expect($service->resolve($fixture['company'], ['alias' => 'ALT-1001'])['matched_by'])->toBe('product_alias')
        ->and($service->resolve($fixture['company'], ['alias' => 'PENDING-1001'])['matched'])->toBeFalse();
});

it('returns suggestions only for unknown fuzzy product and warns for merged products', function (): void {
    $fixture = MasterDataTestSupport::fixture();
    $fixture['product']->forceFill(['lifecycle_status' => 'merged', 'is_active' => false])->save();

    $service = app(ProductIdentityService::class);
    $merged = $service->resolve($fixture['company'], ['sku' => 'SKU-1001']);
    $unknown = $service->resolve($fixture['company'], ['name' => 'Air filter cart']);

    expect($merged['warnings'])->toContain('product_inactive')
        ->and($merged['warnings'])->toContain('product_lifecycle_merged')
        ->and($unknown['matched'])->toBeFalse()
        ->and($unknown['suggestions'])->not->toBeEmpty();
});

it('approves and rejects product aliases with audit events', function (): void {
    $fixture = MasterDataTestSupport::fixture();
    $service = app(ProductIdentityService::class);
    $alias = ProductAlias::factory()->for($fixture['company'])->for($fixture['product'])->create([
        'alias' => 'PENDING-AUDIT',
        'status' => 'pending',
    ]);
    $rejected = ProductAlias::factory()->for($fixture['company'])->for($fixture['product'])->create([
        'alias' => 'REJECT-AUDIT',
        'status' => 'pending',
    ]);

    $service->approveAlias($alias, $fixture['admin'], 'Reviewed source.');
    $service->rejectAlias($rejected, $fixture['admin'], 'Wrong product.');

    expect($alias->refresh()->status->value)->toBe('active')
        ->and($rejected->refresh()->status->value)->toBe('rejected')
        ->and(AuditLog::query()->forEvent('product_alias_approved')->exists())->toBeTrue()
        ->and(AuditLog::query()->forEvent('product_alias_rejected')->exists())->toBeTrue();
});
