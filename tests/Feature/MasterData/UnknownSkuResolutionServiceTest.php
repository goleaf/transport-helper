<?php

use App\Models\MasterDataChangeRequest;
use App\Models\ProductAlias;
use App\Models\UnknownSkuResolution;
use App\Services\Supply\MasterData\UnknownSkuResolutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MasterDataTestSupport;

uses(RefreshDatabase::class);

it('records resolves aliases creates change request and ignores unknown skus with audit safety', function (): void {
    $fixture = MasterDataTestSupport::fixture();
    $service = app(UnknownSkuResolutionService::class);
    $resolution = $service->recordUnknown([
        'company_id' => $fixture['company']->id,
        'supplier_id' => $fixture['supplier']->id,
        'unknown_sku' => 'NEW-001',
        'source_type' => 'import',
    ], $fixture['user'])['resolution'];

    $service->createAliasResolution($resolution, $fixture['product'], 'sku_alias', $fixture['admin'], 'Alias approved.');

    $second = $service->recordUnknown([
        'company_id' => $fixture['company']->id,
        'unknown_sku' => 'NEW-002',
    ], $fixture['user'])['resolution'];
    $service->createProductChangeRequest($second, ['sku' => 'NEW-002', 'name' => 'New product'], $fixture['user'], 'Needs product setup.');

    $third = $service->recordUnknown([
        'company_id' => $fixture['company']->id,
        'unknown_sku' => 'IGNORE-001',
    ], $fixture['user'])['resolution'];
    $service->ignore($third, $fixture['admin'], 'Not a product line.');

    expect(ProductAlias::query()->where('alias', 'NEW-001')->exists())->toBeTrue()
        ->and(MasterDataChangeRequest::query()->where('request_type', 'create_product')->exists())->toBeTrue()
        ->and(UnknownSkuResolution::query()->where('status', 'ignored')->exists())->toBeTrue();
});
