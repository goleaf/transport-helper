<?php

use App\Enums\IncidentSeverity;
use App\Enums\IncidentSlaStatus;
use App\Enums\IncidentType;
use App\Enums\RootCauseCategory;
use App\Models\IncidentCorrectiveAction;
use App\Models\OperationalIncident;
use App\Services\Supply\Incidents\IncidentReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('reports severity, sla, type and root cause distribution', function (): void {
    $incident = OperationalIncident::factory()->create([
        'incident_type' => IncidentType::LogisticsDelay->value,
        'severity' => IncidentSeverity::Critical->value,
        'sla_status' => IncidentSlaStatus::ResolutionBreached->value,
        'root_cause_category' => RootCauseCategory::SupplierDelay->value,
    ]);
    IncidentCorrectiveAction::factory()->create(['operational_incident_id' => $incident->id]);

    $report = app(IncidentReportService::class)->report();

    expect($report['summary']['open_by_severity']['critical'])->toBe(1)
        ->and($report['summary']['sla_breaches'])->toBe(1)
        ->and($report['by_type'][IncidentType::LogisticsDelay->value])->toBe(1)
        ->and($report['root_cause_distribution'][RootCauseCategory::SupplierDelay->value])->toBe(1)
        ->and($report['summary']['corrective_action_completion']['total'])->toBe(1);
});
