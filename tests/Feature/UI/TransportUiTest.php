<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('transport quotes page loads inside supply shell', function (): void {
    $user = User::factory()->create(['role' => UserRole::Admin]);

    $this
        ->actingAs($user)
        ->get(route('supply.transport.quotes.index'))
        ->assertOk()
        ->assertSeeText('Carrier Quotes')
        ->assertSee('aria-label="Supply navigation"', false);
});

test('transport comparison warning says recommendation is not automatic selection', function (): void {
    $view = $this->blade(
        '<x-supply.human-review-banner reason="System recommendation is not automatic carrier selection. User must select carrier." action="Review price, delivery and reliability before selecting." blocking />',
    );

    $view
        ->assertSee('System recommendation is not automatic carrier selection')
        ->assertSee('User must select carrier');
});
