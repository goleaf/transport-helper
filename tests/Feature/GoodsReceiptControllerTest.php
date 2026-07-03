<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\LogisticsTestSupport;

uses(RefreshDatabase::class);

it('receive page loads and record receipt updates quantities', function () {
    $fixture = LogisticsTestSupport::fixture();

    $this->actingAs($fixture['user'])
        ->get(route('supply.logistics.receive.create', $fixture['logisticsRecord']))
        ->assertSuccessful()
        ->assertSee('Record Goods Receipt');

    $this->actingAs($fixture['user'])
        ->post(route('supply.logistics.receive.store', $fixture['logisticsRecord']), LogisticsTestSupport::receiptPayload($fixture))
        ->assertRedirectToRoute('supply.logistics.show', $fixture['logisticsRecord']);

    expect((float) $fixture['supplierOrderItem']->fresh()->received_quantity)->toBe(156.0);
});

it('mismatch requires confirmation', function () {
    $fixture = LogisticsTestSupport::fixture();

    $this->actingAs($fixture['user'])
        ->post(route('supply.logistics.receive.store', $fixture['logisticsRecord']), LogisticsTestSupport::receiptPayload($fixture, [
            'items' => [[
                'product_id' => $fixture['product']->id,
                'received_quantity' => 150,
                'damaged_quantity' => 0,
            ]],
        ]))
        ->assertSessionHasErrors('confirm_mismatches');
});
