<?php

use App\Models\ProductAlias;
use App\Services\Supply\MasterData\MasterDataChangeRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MasterDataTestSupport;

uses(RefreshDatabase::class);

it('creates approves rejects and applies approved alias change requests', function (): void {
    $fixture = MasterDataTestSupport::fixture();
    $service = app(MasterDataChangeRequestService::class);
    $request = $service->createRequest([
        'company_id' => $fixture['company']->id,
        'request_type' => 'create_alias',
        'status' => 'pending_approval',
        'requested_changes_json' => [
            'alias_for' => 'product',
            'product_id' => $fixture['product']->id,
            'alias' => 'CHANGE-ALIAS',
            'alias_type' => 'sku_alias',
            'reason' => 'Approved alias.',
        ],
        'reason' => 'Alias needed from import.',
    ], $fixture['user'])['request'];
    $service->approve($request, $fixture['admin'], 'Approved.');
    $service->apply($request, $fixture['admin']);

    $rejected = $service->createRequest([
        'company_id' => $fixture['company']->id,
        'request_type' => 'create_alias',
        'requested_changes_json' => [],
        'reason' => 'Bad request.',
    ], $fixture['user'])['request'];
    $service->reject($rejected, $fixture['admin'], 'Not enough evidence.');

    expect(ProductAlias::query()->where('alias', 'CHANGE-ALIAS')->exists())->toBeTrue()
        ->and($request->refresh()->status->value)->toBe('applied')
        ->and($rejected->refresh()->status->value)->toBe('rejected');
});
