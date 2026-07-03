<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('dashboard shows kpi cards action queue and environment badges', function (): void {
    $user = User::factory()->create(['role' => UserRole::Admin]);

    $this
        ->actingAs($user)
        ->get(route('supply.dashboard'))
        ->assertOk()
        ->assertSeeText('Supply Dashboard')
        ->assertSeeText('My Action Queue')
        ->assertSeeText('Environment')
        ->assertSeeText('LOCAL MODE')
        ->assertSeeText('EXTERNAL AI OFF')
        ->assertSeeText('REAL INTEGRATIONS OFF')
        ->assertSeeText('No urgent actions');
});

test('dashboard handles empty data safely', function (): void {
    $user = User::factory()->create(['role' => UserRole::SupplyManager]);

    $this
        ->actingAs($user)
        ->get(route('supply.dashboard'))
        ->assertOk()
        ->assertSeeText('No urgent actions')
        ->assertSeeText('No timeline activity yet');
});
