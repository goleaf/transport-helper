<?php

use App\Services\Supply\MasterData\DataStewardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MasterDataTestSupport;

uses(RefreshDatabase::class);

it('assigns and resolves active data stewards', function (): void {
    $fixture = MasterDataTestSupport::fixture();
    $service = app(DataStewardService::class);
    $assignment = $service->assign([
        'company_id' => $fixture['company']->id,
        'user_id' => $fixture['user']->id,
        'stewardship_type' => 'category',
        'category' => 'filters',
    ], $fixture['admin'])['assignment'];

    expect($assignment->is_active)->toBeTrue()
        ->and($service->activeAssignments($fixture['company']))->not->toBeEmpty()
        ->and($service->resolveStewards('category', ['company_id' => $fixture['company']->id, 'category' => 'filters']))->not->toBeEmpty();
});
