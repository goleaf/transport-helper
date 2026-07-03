<?php

use App\Enums\IncidentType;
use App\Enums\UserRole;
use App\Models\OperationalIncident;
use App\Models\User;
use App\Services\Supply\Incidents\IncidentAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('assigns incident to user', function (): void {
    $incident = OperationalIncident::factory()->create();
    $assignee = User::factory()->create(['role' => UserRole::SupplyManager]);
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    app(IncidentAssignmentService::class)->assign($incident, $assignee, $admin, 'Owner selected.');

    expect($incident->fresh()->assigned_user_id)->toBe($assignee->id)
        ->and($incident->events()->where('event_type', 'incident_assigned')->exists())->toBeTrue();
});

it('auto assigns logistics incidents to logistics manager first', function (): void {
    $logisticsManager = User::factory()->create(['role' => UserRole::LogisticsManager]);
    User::factory()->create(['role' => UserRole::SupplyManager]);
    $incident = OperationalIncident::factory()->create(['incident_type' => IncidentType::LogisticsDelay->value]);

    $result = app(IncidentAssignmentService::class)->autoAssign($incident);

    expect($result['assigned'])->toBeTrue()
        ->and($incident->fresh()->assigned_user_id)->toBe($logisticsManager->id);
});
