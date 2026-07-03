<?php

use App\Models\Carrier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\TransportTestSupport;

uses(RefreshDatabase::class);

it('carrier index loads and carrier can be created', function () {
    $fixture = TransportTestSupport::fixture();

    $this->actingAs($fixture['user'])->get(route('supply.carriers.index'))->assertOk();

    $response = $this->actingAs($fixture['user'])->post(route('supply.carriers.store'), [
        'company_id' => $fixture['company']->id,
        'name' => 'New Carrier',
        'code' => 'NEW',
        'default_currency' => 'EUR',
        'reliability_score' => 80,
        'is_active' => true,
    ]);

    $response->assertRedirect();
    expect(Carrier::query()->where('name', 'New Carrier')->exists())->toBeTrue();
});

it('supplier order show has transport panel', function () {
    $fixture = TransportTestSupport::fixture();

    $this->actingAs($fixture['user'])
        ->get(route('supply.supplier-orders.show', $fixture['supplierOrder']))
        ->assertOk()
        ->assertSee('Transport')
        ->assertSee('Compare carrier quotes');
});
