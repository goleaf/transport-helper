<?php

use App\Models\CarrierQuote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\TransportTestSupport;

uses(RefreshDatabase::class);

it('supplier order quotes page and manual create page load', function () {
    $fixture = TransportTestSupport::fixture();
    TransportTestSupport::quote($fixture);

    $this->actingAs($fixture['user'])
        ->get(route('supply.transport.orders.quotes', $fixture['supplierOrder']))
        ->assertOk()
        ->assertSee('System recommendation is not automatic carrier selection');

    $this->actingAs($fixture['user'])
        ->get(route('supply.transport.orders.quotes.create', $fixture['supplierOrder']))
        ->assertOk()
        ->assertSee('Manual Carrier Quote');
});

it('manual quote store creates quote and score route updates scores', function () {
    $fixture = TransportTestSupport::fixture();

    $this->actingAs($fixture['user'])->post(route('supply.transport.orders.quotes.store', $fixture['supplierOrder']), [
        'carrier_id' => $fixture['carrier']->id,
        'price' => 500,
        'currency' => 'EUR',
        'delivery_date' => '2026-07-20',
    ])->assertRedirect(route('supply.transport.orders.quotes', $fixture['supplierOrder']));

    expect(CarrierQuote::query()->count())->toBe(1);

    $this->actingAs($fixture['user'])->post(route('supply.transport.orders.quotes.score', $fixture['supplierOrder']), [
        'required_delivery_date' => '2026-07-20',
    ])->assertRedirect(route('supply.transport.orders.quotes', $fixture['supplierOrder']));

    expect(CarrierQuote::query()->first()->calculated_score)->not->toBeNull();
});

it('quote show loads', function () {
    $fixture = TransportTestSupport::fixture();
    $quote = TransportTestSupport::quote($fixture);

    $this->actingAs($fixture['user'])
        ->get(route('supply.transport.quotes.show', $quote))
        ->assertOk()
        ->assertSee('Carrier Quote');
});
