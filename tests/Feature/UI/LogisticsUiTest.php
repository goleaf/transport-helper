<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('logistics index loads inside supply shell', function (): void {
    $user = User::factory()->create(['role' => UserRole::Admin]);

    $this
        ->actingAs($user)
        ->get(route('supply.logistics.index'))
        ->assertOk()
        ->assertSeeText('Logistics')
        ->assertSee('aria-label="Supply navigation"', false);
});

test('logistics timeline shows expected tracking dates and missing states', function (): void {
    $view = $this->blade('<x-supply.logistics-timeline order-date="2026-07-03" delivery-date="2026-07-18" />');

    $view
        ->assertSee('Logistics timeline')
        ->assertSee('Order date')
        ->assertSee('Delivery date')
        ->assertSee('Actual received')
        ->assertSee('Missing');
});
