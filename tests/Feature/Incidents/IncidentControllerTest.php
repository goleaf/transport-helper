<?php

use App\Enums\IncidentStatus;
use App\Enums\IncidentType;
use App\Enums\UserRole;
use App\Models\OperationalIncident;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('loads incident index create show and report pages for admin', function (): void {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $incident = OperationalIncident::factory()->create();

    $this->actingAs($admin)->get(route('supply.incidents.index'))->assertOk();
    $this->actingAs($admin)->get(route('supply.incidents.create'))->assertOk();
    $this->actingAs($admin)->get(route('supply.incidents.show', $incident))->assertOk();
    $this->actingAs($admin)->get(route('supply.incidents.reports.index'))->assertOk();
});

it('stores incident and validates resolved status needs note', function (): void {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $response = $this->actingAs($admin)->post(route('supply.incidents.store'), [
        'incident_type' => IncidentType::Other->value,
        'title' => 'Manual issue',
    ]);

    $response->assertRedirect();
    $incident = OperationalIncident::query()->firstOrFail();

    $this->actingAs($admin)
        ->post(route('supply.incidents.status', $incident), ['status' => IncidentStatus::Resolved->value])
        ->assertSessionHasErrors('resolution_note');
});

it('viewer cannot close critical incident', function (): void {
    $viewer = User::factory()->create(['role' => UserRole::Viewer]);
    $incident = OperationalIncident::factory()->create();

    $this->actingAs($viewer)
        ->post(route('supply.incidents.status', $incident), ['status' => IncidentStatus::Closed->value])
        ->assertForbidden();
});
