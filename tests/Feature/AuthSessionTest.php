<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects guests from the supply portal to the login page', function (): void {
    $this->get(route('supply.dashboard'))
        ->assertRedirect(route('login'));
});

it('logs users in and redirects them to the intended supply page', function (): void {
    $user = User::factory()->create([
        'email' => 'manager@example.com',
        'password' => 'secret-password',
        'role' => UserRole::SupplyManager,
    ]);

    $this->get(route('supply.dashboard'));

    $this->post(route('login.store'), [
        'email' => 'manager@example.com',
        'password' => 'secret-password',
    ])
        ->assertRedirect(route('supply.dashboard'));

    $this->assertAuthenticatedAs($user);
});

it('logs users out and protects the portal again', function (): void {
    $user = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($user)
        ->post(route('logout'))
        ->assertRedirect(route('login'));

    $this->assertGuest();

    $this->get(route('supply.dashboard'))
        ->assertRedirect(route('login'));
});

it('keeps the seeded demo admin credential valid', function (): void {
    $this->seed();

    $this->post(route('login.store'), [
        'email' => 'test@example.com',
        'password' => 'password',
    ])
        ->assertRedirect(route('supply.dashboard'));

    $this->assertAuthenticated();
});
