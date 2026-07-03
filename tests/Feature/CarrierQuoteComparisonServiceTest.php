<?php

use App\Services\Supply\Transport\CarrierQuoteComparisonService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\TransportTestSupport;

uses(RefreshDatabase::class);

it('compares quotes and does not select best quote automatically', function () {
    $fixture = TransportTestSupport::fixture();
    TransportTestSupport::quote($fixture, ['price' => 500, 'delivery_date' => '2026-07-20']);
    TransportTestSupport::quote($fixture, [
        'carrier_id' => $fixture['lateCarrier']->id,
        'price' => 400,
        'delivery_date' => '2026-07-30',
    ]);

    $comparison = app(CarrierQuoteComparisonService::class)->compareForOrder($fixture['supplierOrder'], ['required_delivery_date' => '2026-07-20']);

    expect($comparison['ranked_quotes'])->toHaveCount(2)
        ->and($comparison['requires_human_selection'])->toBeTrue()
        ->and($comparison['message'])->toContain('User must select carrier')
        ->and($fixture['supplierOrder']->carrierQuotes()->where('status', 'selected')->exists())->toBeFalse();
});
