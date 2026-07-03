<?php

use App\Enums\IncidentPriority;
use App\Enums\IncidentSeverity;
use App\Enums\IncidentSlaStatus;
use App\Enums\IncidentStatus;
use App\Enums\IncidentType;
use App\Models\Company;
use App\Models\IncidentSlaPolicy;
use App\Models\OperationalIncident;
use App\Services\Supply\Incidents\IncidentSlaService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('uses default sla for critical incidents', function (): void {
    $policy = app(IncidentSlaService::class)->policyFor(
        IncidentType::SecurityWarning->value,
        IncidentSeverity::Critical->value,
        IncidentPriority::P1->value,
    );

    expect($policy['response_minutes'])->toBe(60)
        ->and($policy['resolution_minutes'])->toBe(480);
});

it('uses custom active sla policy', function (): void {
    $company = Company::factory()->create();
    IncidentSlaPolicy::factory()->create([
        'company_id' => $company->id,
        'severity' => IncidentSeverity::High->value,
        'response_minutes' => 30,
        'resolution_minutes' => 90,
        'is_active' => true,
    ]);

    $policy = app(IncidentSlaService::class)->policyFor(
        IncidentType::LogisticsDelay->value,
        IncidentSeverity::High->value,
        IncidentPriority::P2->value,
        $company->id,
    );

    expect($policy['response_minutes'])->toBe(30)
        ->and($policy['resolution_minutes'])->toBe(90);
});

it('assigns due dates and detects breaches', function (): void {
    $incident = OperationalIncident::factory()->create([
        'severity' => IncidentSeverity::Critical->value,
        'priority' => IncidentPriority::P1->value,
        'incident_type' => IncidentType::LogisticsDelay->value,
        'created_at' => now()->subHours(2),
        'status' => IncidentStatus::Open->value,
    ]);

    app(IncidentSlaService::class)->assignDueDates($incident);
    $incident->refresh();

    expect($incident->response_due_at)->not->toBeNull()
        ->and($incident->resolution_due_at)->not->toBeNull();

    $result = app(IncidentSlaService::class)->evaluate($incident);

    expect($result['sla_status'])->toBe(IncidentSlaStatus::ResponseBreached->value);
});

it('marks completed within sla', function (): void {
    $incident = OperationalIncident::factory()->create([
        'severity' => IncidentSeverity::Low->value,
        'priority' => IncidentPriority::P4->value,
        'first_response_at' => now(),
        'resolved_at' => now(),
        'response_due_at' => now()->addDay(),
        'resolution_due_at' => now()->addDays(2),
        'status' => IncidentStatus::Resolved->value,
    ]);

    $result = app(IncidentSlaService::class)->evaluate($incident);

    expect($result['sla_status'])->toBe(IncidentSlaStatus::CompletedWithinSla->value);
});
