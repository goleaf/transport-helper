<?php

use App\Models\MasterDataMergeProposal;
use App\Models\Product;
use App\Models\UnknownSkuResolution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MasterDataTestSupport;

uses(RefreshDatabase::class);

it('runs master data quality duplicate unknown sku and governance commands', function (): void {
    $fixture = MasterDataTestSupport::fixture();
    Product::factory()->for($fixture['company'])->create(['manufacturer_sku' => 'MFG-1001']);
    UnknownSkuResolution::factory()->for($fixture['company'])->create(['unknown_sku' => 'CMD-UNKNOWN', 'status' => 'unresolved']);

    $this->artisan('supply:master-data-quality-audit', ['--company_id' => $fixture['company']->id])->assertSuccessful();
    $this->artisan('supply:detect-master-data-duplicates', ['--company_id' => $fixture['company']->id])->assertSuccessful();
    $this->artisan('supply:unknown-sku-report', ['--company_id' => $fixture['company']->id])->assertSuccessful();
    $this->artisan('supply:master-data-governance-report', ['--company_id' => $fixture['company']->id, '--json' => true])->assertSuccessful();
});

it('detect duplicates command does not create proposals by default', function (): void {
    $fixture = MasterDataTestSupport::fixture();
    Product::factory()->for($fixture['company'])->create(['manufacturer_sku' => 'MFG-1001']);

    $this->artisan('supply:detect-master-data-duplicates', ['--company_id' => $fixture['company']->id])->assertSuccessful();

    expect(MasterDataMergeProposal::query()->exists())->toBeFalse();
});
