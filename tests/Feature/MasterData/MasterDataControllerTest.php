<?php

use App\Models\MasterDataMergeProposal;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MasterDataTestSupport;

uses(RefreshDatabase::class);

it('loads master data pages and creates aliases mappings unknown skus change requests and merge proposals', function (): void {
    $fixture = MasterDataTestSupport::fixture();
    $this->actingAs($fixture['admin']);

    $this->get(route('supply.master-data.dashboard'))->assertOk();
    $this->get(route('supply.master-data.product-aliases.index'))->assertOk();
    $this->post(route('supply.master-data.product-aliases.store'), [
        'company_id' => $fixture['company']->id,
        'product_id' => $fixture['product']->id,
        'alias' => 'WEB-ALIAS',
        'alias_type' => 'sku_alias',
        'reason' => 'Controller alias.',
    ])->assertRedirect();

    $this->get(route('supply.master-data.supplier-aliases.index'))->assertOk();
    $this->post(route('supply.master-data.supplier-aliases.store'), [
        'company_id' => $fixture['company']->id,
        'supplier_id' => $fixture['supplier']->id,
        'alias' => 'Web Supplier Alias',
        'alias_type' => 'name_alias',
        'reason' => 'Controller supplier alias.',
    ])->assertRedirect();

    $this->get(route('supply.master-data.supplier-product-identities.index'))->assertOk();
    $this->post(route('supply.master-data.supplier-product-identities.store'), [
        'company_id' => $fixture['company']->id,
        'supplier_id' => $fixture['supplier']->id,
        'product_id' => $fixture['product']->id,
        'supplier_sku' => 'WEB-SUP-SKU',
        'reason' => 'Controller mapping.',
    ])->assertRedirect();

    $this->get(route('supply.master-data.unknown-skus.index'))->assertOk();
    $this->post(route('supply.master-data.unknown-skus.store'), [
        'company_id' => $fixture['company']->id,
        'supplier_id' => $fixture['supplier']->id,
        'unknown_sku' => 'WEB-UNKNOWN',
        'source_type' => 'manual',
    ])->assertRedirect();

    $this->get(route('supply.master-data.change-requests.index'))->assertOk();
    $this->post(route('supply.master-data.change-requests.store'), [
        'company_id' => $fixture['company']->id,
        'request_type' => 'create_alias',
        'reason' => 'Controller change request.',
    ])->assertRedirect();

    $target = Product::factory()->for($fixture['company'])->create(['sku' => 'WEB-TARGET']);
    $this->get(route('supply.master-data.merge-proposals.index'))->assertOk();
    $this->post(route('supply.master-data.merge-proposals.store'), [
        'merge_type' => 'product',
        'source_id' => $fixture['product']->id,
        'target_id' => $target->id,
        'reason' => 'Controller merge proposal.',
    ])->assertRedirect();

    $this->get(route('supply.master-data.stewards.index'))->assertOk();
    $this->get(route('supply.master-data.reports.quality'))->assertOk();
});

it('viewer cannot execute merge proposal', function (): void {
    $fixture = MasterDataTestSupport::fixture();
    $target = Product::factory()->for($fixture['company'])->create(['sku' => 'VIEWER-TARGET']);
    $proposal = MasterDataMergeProposal::factory()->for($fixture['company'])->create([
        'merge_type' => 'product',
        'source_model_type' => Product::class,
        'source_model_id' => $fixture['product']->id,
        'target_model_type' => Product::class,
        'target_model_id' => $target->id,
        'status' => 'approved',
    ]);

    $this->actingAs($fixture['viewer'])
        ->post(route('supply.master-data.merge-proposals.execute', $proposal), ['confirmation' => '1'])
        ->assertForbidden();
});
