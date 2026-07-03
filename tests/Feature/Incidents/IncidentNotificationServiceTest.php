<?php

use App\Models\OperationalIncident;
use App\Models\User;
use App\Services\Supply\Incidents\IncidentNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

it('creates database notification for assignee without external email', function (): void {
    Mail::fake();
    $user = User::factory()->create(['role' => 'supply_manager']);
    $incident = OperationalIncident::factory()->create(['assigned_user_id' => $user->id]);

    $result = app(IncidentNotificationService::class)->notify($incident, 'incident_assigned');

    expect($result['skipped_reason'])->toBeNull()
        ->and($user->notifications()->count())->toBe(1);
    Mail::assertNothingSent();
});

it('dedupes matching notification keys', function (): void {
    $user = User::factory()->create(['role' => 'supply_manager']);
    $incident = OperationalIncident::factory()->create(['assigned_user_id' => $user->id]);
    $service = app(IncidentNotificationService::class);

    $service->notify($incident, 'incident_assigned');
    $service->notify($incident, 'incident_assigned');

    expect($user->notifications()->count())->toBe(1);
});
