<?php

use App\Models\MasterDataMergeProposal;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\Supply\MasterData\MasterDataMergeExecutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MasterDataTestSupport;

uses(RefreshDatabase::class);

it('executes approved product and supplier merges safely and rejects unapproved merge', function (): void {
    $fixture = MasterDataTestSupport::fixture();
    $service = app(MasterDataMergeExecutionService::class);
    $targetProduct = Product::factory()->for($fixture['company'])->create(['sku' => 'SKU-9001']);
    $targetSupplier = Supplier::factory()->for($fixture['company'])->create(['name' => 'Target Supplier']);

    $unapproved = MasterDataMergeProposal::factory()->for($fixture['company'])->create([
        'merge_type' => 'product',
        'source_model_type' => Product::class,
        'source_model_id' => $fixture['product']->id,
        'target_model_type' => Product::class,
        'target_model_id' => $targetProduct->id,
        'status' => 'draft',
    ]);
    $service->execute($unapproved, $fixture['admin']);
})->throws(InvalidArgumentException::class);

it('marks source records merged and not deleted during approved execution', function (): void {
    $fixture = MasterDataTestSupport::fixture();
    $targetProduct = Product::factory()->for($fixture['company'])->create(['sku' => 'SKU-9002']);
    $targetSupplier = Supplier::factory()->for($fixture['company'])->create(['name' => 'Target Supplier']);
    $service = app(MasterDataMergeExecutionService::class);

    $productProposal = MasterDataMergeProposal::factory()->for($fixture['company'])->create([
        'merge_type' => 'product',
        'source_model_type' => Product::class,
        'source_model_id' => $fixture['product']->id,
        'target_model_type' => Product::class,
        'target_model_id' => $targetProduct->id,
        'status' => 'approved',
    ]);
    $supplierProposal = MasterDataMergeProposal::factory()->for($fixture['company'])->create([
        'merge_type' => 'supplier',
        'source_model_type' => Supplier::class,
        'source_model_id' => $fixture['supplier']->id,
        'target_model_type' => Supplier::class,
        'target_model_id' => $targetSupplier->id,
        'status' => 'approved',
    ]);

    $service->execute($productProposal, $fixture['admin']);
    $service->execute($supplierProposal, $fixture['admin']);

    expect($fixture['product']->refresh()->lifecycle_status)->toBe('merged')
        ->and($fixture['supplier']->refresh()->lifecycle_status)->toBe('merged')
        ->and(Product::query()->whereKey($fixture['product']->id)->exists())->toBeTrue()
        ->and(Supplier::query()->whereKey($fixture['supplier']->id)->exists())->toBeTrue();
});
