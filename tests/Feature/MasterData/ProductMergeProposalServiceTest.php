<?php

use App\Models\Product;
use App\Services\Supply\MasterData\ProductMergeProposalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MasterDataTestSupport;

uses(RefreshDatabase::class);

it('creates previews approves and rejects product merge proposals', function (): void {
    $fixture = MasterDataTestSupport::fixture();
    $target = Product::factory()->for($fixture['company'])->create(['sku' => 'SKU-2002']);
    $service = app(ProductMergeProposalService::class);
    $proposal = $service->createProposal($fixture['product'], $target, $fixture['admin'], 'Duplicate SKU cleanup.')['proposal'];
    $preview = $service->preview($proposal);
    $service->approve($proposal, $fixture['admin'], 'Impact reviewed.');

    $other = Product::factory()->for($fixture['company'])->create(['sku' => 'SKU-3003']);
    $rejected = $service->createProposal($other, $target, $fixture['admin'], 'Rejected duplicate cleanup.')['proposal'];
    $service->reject($rejected, $fixture['admin'], 'Not a duplicate.');

    expect($preview['affected_tables'])->toHaveKey('supplier_product_rules')
        ->and($proposal->refresh()->status->value)->toBe('approved')
        ->and($rejected->refresh()->status->value)->toBe('rejected');
});
