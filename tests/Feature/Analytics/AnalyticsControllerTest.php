<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\AnalyticsTestSupport;

uses(RefreshDatabase::class);

it('loads analytics dashboard and report pages for authorized users', function (): void {
    $this->seed(RolePermissionSeeder::class);
    AnalyticsTestSupport::fixture();
    $user = User::factory()->create(['role' => 'admin']);

    $this->actingAs($user)
        ->get(route('supply.analytics.dashboard'))
        ->assertOk()
        ->assertSee('Management Analytics');

    $this->actingAs($user)
        ->get(route('supply.analytics.reports.show', ['reportType' => 'supplier_performance']))
        ->assertOk()
        ->assertSee('Supplier Performance');
});

it('blocks users without analytics permission', function (): void {
    $user = User::factory()->create(['role' => 'viewer']);

    $this->actingAs($user)
        ->get(route('supply.analytics.dashboard'))
        ->assertForbidden();
});

it('exports analytics reports through the route', function (): void {
    $this->seed(RolePermissionSeeder::class);
    AnalyticsTestSupport::fixture();
    $user = User::factory()->create(['role' => 'admin']);

    $this->actingAs($user)
        ->post(route('supply.analytics.reports.export', ['reportType' => 'stockout_risk']), [
            'format' => 'json',
        ])
        ->assertRedirect();
});
