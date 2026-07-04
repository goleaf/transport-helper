<?php

use App\Services\Supply\MasterData\SupplierLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MasterDataTestSupport;

uses(RefreshDatabase::class);

it('changes supplier lifecycle with reason and sets inactive when blocked', function (): void {
    $fixture = MasterDataTestSupport::fixture();

    $result = app(SupplierLifecycleService::class)->changeStatus($fixture['supplier'], 'blocked', $fixture['admin'], 'Quality issue.');

    expect($result['supplier']->lifecycle_status)->toBe('blocked')
        ->and($result['supplier']->is_active)->toBeFalse();
});
