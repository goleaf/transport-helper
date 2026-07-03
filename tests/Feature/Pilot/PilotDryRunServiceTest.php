<?php

use App\Models\AuditLog;
use App\Models\PilotRun;
use App\Models\PilotSupplier;
use App\Models\User;
use App\Services\Supply\Pilot\PilotDryRunService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('runs safe dry-runs without external side effects or carrier selection', function (): void {
    $pilot = PilotSupplier::factory()->create();
    $user = User::factory()->create(['role' => 'admin']);

    $result = app(PilotDryRunService::class)->runFullUatDryRun($pilot, $user);

    expect($result['result']['real_email_sent'])->toBeFalse()
        ->and($result['result']['external_api_called'])->toBeFalse()
        ->and($result['result']['external_ai_called'])->toBeFalse()
        ->and($result['result']['carrier_auto_selected'])->toBeFalse()
        ->and(PilotRun::query()->where('run_type', 'full_uat_dry_run')->exists())->toBeTrue()
        ->and(AuditLog::query()->where('event_type', 'pilot_dry_run_completed')->exists())->toBeTrue();
});
