<?php

use App\Enums\IncidentPriority;
use App\Enums\IncidentSeverity;
use App\Enums\IncidentSourceType;
use App\Enums\IncidentStatus;
use App\Enums\IncidentType;
use App\Models\OperationalIncident;
use App\Models\User;
use App\Services\Supply\Incidents\IncidentCreationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates manual incident with number and sla', function (): void {
    $user = User::factory()->create(['role' => 'admin']);

    $result = app(IncidentCreationService::class)->create([
        'incident_type' => IncidentType::Other->value,
        'severity' => IncidentSeverity::Medium->value,
        'priority' => IncidentPriority::P3->value,
        'title' => 'Manual blocker',
        'description' => 'Needs operator review.',
        'source_type' => IncidentSourceType::Manual->value,
    ], $user);

    $incident = $result['incident'];

    expect($incident)->toBeInstanceOf(OperationalIncident::class)
        ->and($incident->incident_number)->toStartWith('INC-')
        ->and($incident->status->value)->toBe(IncidentStatus::Open->value)
        ->and($incident->response_due_at)->not->toBeNull()
        ->and($incident->events()->where('event_type', 'incident_created')->exists())->toBeTrue();
});

it('deduplicates active incidents for same source and type', function (): void {
    $user = User::factory()->create(['role' => 'admin']);
    $payload = [
        'incident_type' => IncidentType::ImportFailure->value,
        'severity' => IncidentSeverity::High->value,
        'priority' => IncidentPriority::P2->value,
        'title' => 'Import failed',
        'source_type' => IncidentSourceType::ImportBatch->value,
        'source_id' => 10,
    ];

    $first = app(IncidentCreationService::class)->create($payload, $user)['incident'];
    $second = app(IncidentCreationService::class)->create($payload, $user);

    expect($second['deduped'])->toBeTrue()
        ->and($second['incident']->id)->toBe($first->id)
        ->and($second['incident']->occurrence_count)->toBe(2)
        ->and(OperationalIncident::query()->count())->toBe(1);
});

it('creates incident for source through resolver', function (): void {
    $result = app(IncidentCreationService::class)->createForSource(
        IncidentType::LogisticsDelay->value,
        null,
        [
            'source_type' => IncidentSourceType::LogisticsRecord->value,
            'source_id' => 44,
            'source_label' => 'Logistics #44',
        ],
    );

    expect($result['incident']->incident_type->value)->toBe(IncidentType::LogisticsDelay->value)
        ->and($result['incident']->severity->value)->toBe(IncidentSeverity::High->value);
});
