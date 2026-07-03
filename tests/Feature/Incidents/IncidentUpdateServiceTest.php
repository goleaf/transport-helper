<?php

use App\Enums\IncidentSeverity;
use App\Enums\IncidentStatus;
use App\Enums\RootCauseCategory;
use App\Enums\UserRole;
use App\Models\IncidentCorrectiveAction;
use App\Models\OperationalIncident;
use App\Models\User;
use App\Services\Supply\Incidents\IncidentUpdateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('requires a resolution note before resolving', function (): void {
    $incident = OperationalIncident::factory()->create();
    $user = User::factory()->create(['role' => UserRole::Admin]);

    expect(fn () => app(IncidentUpdateService::class)->changeStatus($incident, IncidentStatus::Resolved->value, $user))
        ->toThrow(ValidationException::class);
});

it('requires root cause or no action reason before closing critical incident', function (): void {
    $incident = OperationalIncident::factory()->create([
        'severity' => IncidentSeverity::Critical->value,
        'status' => IncidentStatus::Resolved->value,
        'resolution_note' => 'Resolved after review.',
    ]);
    $user = User::factory()->create(['role' => UserRole::Admin]);

    expect(fn () => app(IncidentUpdateService::class)->changeStatus($incident, IncidentStatus::Closed->value, $user))
        ->toThrow(ValidationException::class);
});

it('changes status and writes incident event', function (): void {
    $incident = OperationalIncident::factory()->create([
        'severity' => IncidentSeverity::Critical->value,
        'root_cause_category' => RootCauseCategory::DataQuality->value,
        'root_cause_summary' => 'Bad import mapping.',
        'resolution_note' => 'Operator fixed mapping.',
    ]);
    IncidentCorrectiveAction::factory()->create(['operational_incident_id' => $incident->id]);
    $user = User::factory()->create(['role' => UserRole::Admin]);

    app(IncidentUpdateService::class)->changeStatus($incident, IncidentStatus::Closed->value, $user);

    expect($incident->fresh()->status)->toBe(IncidentStatus::Closed)
        ->and($incident->events()->where('event_type', 'incident_closed')->exists())->toBeTrue();
});

it('adds incident comment', function (): void {
    $incident = OperationalIncident::factory()->create();
    $user = User::factory()->create(['role' => UserRole::Admin]);

    app(IncidentUpdateService::class)->addComment($incident, 'Review started.', $user);

    expect($incident->comments()->where('comment', 'Review started.')->exists())->toBeTrue();
});
