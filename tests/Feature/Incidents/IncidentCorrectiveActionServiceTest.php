<?php

use App\Enums\CorrectiveActionStatus;
use App\Enums\IncidentSeverity;
use App\Enums\UserRole;
use App\Models\IncidentCorrectiveAction;
use App\Models\OperationalIncident;
use App\Models\User;
use App\Services\Supply\Incidents\IncidentCorrectiveActionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('creates corrective action and requires due date for critical incident', function (): void {
    $incident = OperationalIncident::factory()->create(['severity' => IncidentSeverity::Critical->value]);
    $user = User::factory()->create(['role' => UserRole::Admin]);

    expect(fn () => app(IncidentCorrectiveActionService::class)->createAction($incident, [
        'title' => 'Fix process',
    ], $user))->toThrow(ValidationException::class);

    $result = app(IncidentCorrectiveActionService::class)->createAction($incident, [
        'title' => 'Fix process',
        'due_date' => now()->addDay()->toDateString(),
    ], $user);

    expect($result['action']->status)->toBe(CorrectiveActionStatus::Open);
});

it('marks action done and verified', function (): void {
    $action = IncidentCorrectiveAction::factory()->create();
    $user = User::factory()->create(['role' => UserRole::Admin]);

    app(IncidentCorrectiveActionService::class)->markDone($action, $user, 'Fixed and checked.');
    app(IncidentCorrectiveActionService::class)->verify($action->fresh(), $user, 'Verified.');

    expect($action->fresh()->status)->toBe(CorrectiveActionStatus::Verified);
});
