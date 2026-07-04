<?php

use App\Models\MasterDataMergeProposal;
use App\Models\Product;
use App\Models\ProductAlias;
use App\Models\UnknownSkuResolution;
use App\Services\Supply\MasterData\MasterDataDuplicateDetectionService;
use App\Services\Supply\MasterData\MasterDataMergeExecutionService;
use App\Services\Supply\MasterData\UnknownSkuResolutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MasterDataTestSupport;

uses(RefreshDatabase::class);

it('has no dto app data or forbidden external dependencies in master data services', function (): void {
    expect(is_dir(app_path('Data')))->toBeFalse();

    $files = collect(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path('Services/Supply/MasterData'))))
        ->filter(fn (SplFileInfo $file): bool => $file->isFile())
        ->map(fn (SplFileInfo $file): string => $file->getPathname());
    $source = $files->map(fn (string $path): string => file_get_contents($path) ?: '')->implode("\n");

    expect($files->filter(fn (string $file): bool => str_contains($file, 'DTO') || str_contains($file, 'Dto'))->values()->all())->toBe([])
        ->and($source)->not->toContain('OpenAI')
        ->and($source)->not->toContain('Http::')
        ->and($source)->not->toContain('Guzzle')
        ->and($source)->not->toContain('EmailSenderInterface')
        ->and($source)->not->toContain('CarrierSelectionService')
        ->and($source)->not->toContain('SupplierOrderSendService');
});

it('duplicate detection and unknown sku tracking do not auto merge or auto create products', function (): void {
    $fixture = MasterDataTestSupport::fixture();
    Product::factory()->for($fixture['company'])->create(['manufacturer_sku' => 'MFG-1001', 'name' => 'Air filter cartridge copy']);

    app(MasterDataDuplicateDetectionService::class)->detectProductDuplicates($fixture['company']);
    app(UnknownSkuResolutionService::class)->recordUnknown([
        'company_id' => $fixture['company']->id,
        'supplier_id' => $fixture['supplier']->id,
        'unknown_sku' => 'NEW-UNKNOWN',
    ], $fixture['user']);

    expect(Product::query()->where('sku', 'NEW-UNKNOWN')->exists())->toBeFalse()
        ->and(MasterDataMergeProposal::query()->exists())->toBeFalse()
        ->and(UnknownSkuResolution::query()->where('unknown_sku', 'NEW-UNKNOWN')->exists())->toBeTrue();
});

it('merge execution requires approval and source product is not hard deleted', function (): void {
    $fixture = MasterDataTestSupport::fixture();
    $target = Product::factory()->for($fixture['company'])->create(['sku' => 'SKU-2002']);
    $proposal = MasterDataMergeProposal::factory()->for($fixture['company'])->create([
        'merge_type' => 'product',
        'source_model_type' => Product::class,
        'source_model_id' => $fixture['product']->id,
        'target_model_type' => Product::class,
        'target_model_id' => $target->id,
        'status' => 'approved',
    ]);

    app(MasterDataMergeExecutionService::class)->execute($proposal, $fixture['admin']);

    expect($fixture['product']->refresh()->lifecycle_status)->toBe('merged')
        ->and(Product::query()->whereKey($fixture['product']->id)->exists())->toBeTrue()
        ->and(ProductAlias::query()->where('product_id', $target->id)->exists())->toBeTrue();
});
