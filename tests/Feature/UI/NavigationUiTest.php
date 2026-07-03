<?php

use App\Enums\UserRole;
use App\Models\User;
use App\Services\Supply\UI\SupplyNavigationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('sidebar navigation service renders main groups for admin', function (): void {
    $user = User::factory()->create(['role' => UserRole::Admin]);

    $groups = app(SupplyNavigationService::class)->navigation($user);

    expect(collect($groups)->pluck('label')->all())
        ->toContain('Supply')
        ->toContain('Communication')
        ->toContain('Transport & Logistics')
        ->toContain('Pilot & Integrations')
        ->toContain('Admin');
});

test('viewer does not see admin only links', function (): void {
    $user = User::factory()->create(['role' => UserRole::Viewer]);

    $groups = app(SupplyNavigationService::class)->navigation($user);
    $labels = collect($groups)->flatMap(fn (array $group): array => collect($group['items'])->pluck('label')->all())->all();

    expect($labels)
        ->not->toContain('Settings')
        ->not->toContain('Health Check');
});

test('supply layout renders topbar and sidebar landmarks', function (): void {
    $user = User::factory()->create(['role' => UserRole::Admin]);

    $this
        ->actingAs($user)
        ->get(route('supply.dashboard'))
        ->assertOk()
        ->assertSee('aria-label="Supply navigation"', false)
        ->assertSee('Search SKU, order, email, supplier')
        ->assertSeeText('Pilot UAT');
});
