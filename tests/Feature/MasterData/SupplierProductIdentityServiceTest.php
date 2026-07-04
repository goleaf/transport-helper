<?php

use App\Models\SupplierProductIdentity;
use App\Models\SupplierProductRule;
use App\Services\Supply\MasterData\ProductIdentityService;
use App\Services\Supply\MasterData\SupplierProductIdentityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MasterDataTestSupport;

uses(RefreshDatabase::class);

it('creates pending mapping for non admin approves mapping and syncs supplier product rule', function (): void {
    $fixture = MasterDataTestSupport::fixture();
    $service = app(SupplierProductIdentityService::class);
    $identity = $service->createMapping([
        'company_id' => $fixture['company']->id,
        'supplier_id' => $fixture['supplier']->id,
        'product_id' => $fixture['product']->id,
        'supplier_sku' => 'PENDING-SUP',
        'reason' => 'Supplier catalog mapping.',
    ], $fixture['user'])['identity'];

    expect($identity->status->value)->toBe('pending')
        ->and(app(ProductIdentityService::class)->resolve($fixture['company'], ['supplier_sku' => 'PENDING-SUP'], $fixture['supplier'])['matched'])->toBeFalse();

    $service->approveMapping($identity, $fixture['admin'], 'Approved mapping.');
    $rule = $service->syncToSupplierProductRule($identity->refresh(), $fixture['admin'])['rule'];

    expect($identity->refresh()->status->value)->toBe('active')
        ->and($rule)->toBeInstanceOf(SupplierProductRule::class)
        ->and(SupplierProductIdentity::query()->count())->toBe(1);
});
