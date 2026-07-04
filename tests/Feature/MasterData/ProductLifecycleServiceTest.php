<?php

use App\Services\Supply\MasterData\ProductLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MasterDataTestSupport;

uses(RefreshDatabase::class);

it('changes product lifecycle with reason and sets inactive for discontinued', function (): void {
    $fixture = MasterDataTestSupport::fixture();

    $result = app(ProductLifecycleService::class)->changeStatus($fixture['product'], 'discontinued', $fixture['admin'], 'No longer available.');

    expect($result['product']->lifecycle_status)->toBe('discontinued')
        ->and($result['product']->is_active)->toBeFalse();
});

it('requires replacement product for replaced status', function (): void {
    $fixture = MasterDataTestSupport::fixture();

    app(ProductLifecycleService::class)->changeStatus($fixture['product'], 'replaced', $fixture['admin'], 'Replaced.');
})->throws(InvalidArgumentException::class);
