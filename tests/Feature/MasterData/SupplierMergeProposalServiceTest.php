<?php

use App\Models\Supplier;
use App\Services\Supply\MasterData\SupplierMergeProposalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MasterDataTestSupport;

uses(RefreshDatabase::class);

it('creates previews approves and rejects supplier merge proposals', function (): void {
    $fixture = MasterDataTestSupport::fixture();
    $target = Supplier::factory()->for($fixture['company'])->create(['name' => 'Nordic Parts Target']);
    $service = app(SupplierMergeProposalService::class);
    $proposal = $service->createProposal($fixture['supplier'], $target, $fixture['admin'], 'Supplier duplicate cleanup.')['proposal'];
    $preview = $service->preview($proposal);
    $service->approve($proposal, $fixture['admin'], 'Impact reviewed.');

    $other = Supplier::factory()->for($fixture['company'])->create();
    $rejected = $service->createProposal($other, $target, $fixture['admin'], 'Rejected supplier cleanup.')['proposal'];
    $service->reject($rejected, $fixture['admin'], 'Not a duplicate.');

    expect($preview['affected_tables'])->toHaveKey('supplier_contacts')
        ->and($proposal->refresh()->status->value)->toBe('approved')
        ->and($rejected->refresh()->status->value)->toBe('rejected');
});
