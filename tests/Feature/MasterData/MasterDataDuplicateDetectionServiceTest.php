<?php

use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierContact;
use App\Models\SupplierProductRule;
use App\Services\Supply\MasterData\MasterDataDuplicateDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MasterDataTestSupport;

uses(RefreshDatabase::class);

it('detects duplicate products suppliers and supplier sku conflicts without merging records', function (): void {
    $fixture = MasterDataTestSupport::fixture();
    $duplicateProduct = Product::factory()->for($fixture['company'])->create([
        'manufacturer_sku' => 'MFG-1001',
        'name' => 'Air filter cartridge duplicate',
        'category' => 'filters',
        'brand' => 'Acme',
    ]);
    $duplicateSupplier = Supplier::factory()->for($fixture['company'])->create(['name' => 'Nordic Parts', 'code' => 'NORDIC-2']);
    SupplierContact::factory()->for($duplicateSupplier)->create(['email' => 'copy@nordic.test']);
    SupplierProductRule::factory()->for($fixture['supplier'])->for($duplicateProduct)->create(['supplier_sku' => 'SUP-1001']);

    $service = app(MasterDataDuplicateDetectionService::class);

    expect($service->detectProductDuplicates($fixture['company']))->not->toBeEmpty()
        ->and($service->detectSupplierDuplicates($fixture['company']))->not->toBeEmpty()
        ->and($service->detectSupplierSkuConflicts($fixture['company']))->not->toBeEmpty();
});
