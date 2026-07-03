<?php

use App\Enums\UserRole;
use App\Models\IntegrationConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('health pilot and integrations pages load for admin', function (): void {
    $user = User::factory()->create(['role' => UserRole::Admin]);

    $this
        ->actingAs($user)
        ->get(route('supply.health.index'))
        ->assertOk()
        ->assertSeeText('Supply Health');

    $this
        ->actingAs($user)
        ->get(route('supply.pilots.index'))
        ->assertOk()
        ->assertSeeText('Pilot Suppliers');

    $this
        ->actingAs($user)
        ->get(route('supply.integrations.index'))
        ->assertOk()
        ->assertSeeText('Integrations');
});

test('integration show page does not expose encrypted credentials', function (): void {
    $user = User::factory()->create(['role' => UserRole::Admin]);
    $connection = IntegrationConnection::factory()->create([
        'name' => 'Safe test Gmail',
        'provider' => 'gmail',
        'encrypted_config' => [
            'client_secret' => 'super-secret-value',
            'refresh_token' => 'refresh-secret-value',
        ],
    ]);

    $this
        ->actingAs($user)
        ->get(route('supply.integrations.show', $connection))
        ->assertOk()
        ->assertSeeText('Safe test Gmail')
        ->assertDontSee('super-secret-value')
        ->assertDontSee('refresh-secret-value');
});
