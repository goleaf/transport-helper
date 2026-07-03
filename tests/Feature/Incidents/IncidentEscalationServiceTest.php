<?php

use App\Enums\EscalationStatus;
use App\Enums\IncidentPriority;
use App\Enums\IncidentSeverity;
use App\Models\OperationalIncident;
use App\Models\User;
use App\Services\Supply\Incidents\IncidentEscalationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('escalates incident and writes audit event', function (): void {
    $incident = OperationalIncident::factory()->create();
    $user = User::factory()->create(['role' => 'admin']);

    $result = app(IncidentEscalationService::class)->escalate($incident, 'P1 response breached.', $user);

    expect($result['escalation']->status)->toBe(EscalationStatus::Open)
        ->and($incident->escalations()->count())->toBe(1)
        ->and($incident->events()->where('event_type', 'incident_escalated')->exists())->toBeTrue();
});

it('monitor escalates p1 incidents', function (): void {
    OperationalIncident::factory()->create([
        'severity' => IncidentSeverity::Critical->value,
        'priority' => IncidentPriority::P1->value,
    ]);

    $result = app(IncidentEscalationService::class)->monitorEscalations();

    expect($result['escalations_created'])->toBe(1);
});
