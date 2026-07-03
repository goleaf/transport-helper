<?php

use App\Enums\RootCauseCategory;
use App\Enums\UserRole;
use App\Models\OperationalIncident;
use App\Models\User;
use App\Services\Supply\Incidents\IncidentRootCauseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('sets root cause details', function (): void {
    $incident = OperationalIncident::factory()->create();
    $user = User::factory()->create(['role' => UserRole::Admin]);

    app(IncidentRootCauseService::class)->setRootCause($incident, [
        'root_cause_category' => RootCauseCategory::SupplierMismatch->value,
        'root_cause_summary' => 'Supplier confirmed different quantity.',
        'prevention_notes' => 'Review supplier pack rule.',
        'corrective_action_required' => true,
    ], $user);

    expect($incident->fresh()->root_cause_category)->toBe(RootCauseCategory::SupplierMismatch->value)
        ->and($incident->events()->where('event_type', 'incident_root_cause_updated')->exists())->toBeTrue();
});

it('requires root cause summary', function (): void {
    $incident = OperationalIncident::factory()->create();
    $user = User::factory()->create(['role' => UserRole::Admin]);

    expect(fn () => app(IncidentRootCauseService::class)->setRootCause($incident, [
        'root_cause_category' => RootCauseCategory::Unknown->value,
    ], $user))->toThrow(ValidationException::class);
});
