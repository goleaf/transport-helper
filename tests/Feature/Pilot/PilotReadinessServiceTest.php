<?php

use App\Enums\PilotSupplierStatus;
use App\Models\AuditLog;
use App\Models\PilotSupplier;
use App\Models\User;
use App\Services\Supply\Pilot\PilotReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a failed readiness run and stores result when files are missing', function (): void {
    $pilot = PilotSupplier::factory()->create();
    $user = User::factory()->create(['role' => 'admin']);

    $result = app(PilotReadinessService::class)->check($pilot, $user);

    expect($result['run']->status)->toBe('failed')
        ->and($pilot->fresh()->status)->toBe(PilotSupplierStatus::Blocked->value)
        ->and($pilot->fresh()->readiness_result_json['status'])->toBe('failed')
        ->and(AuditLog::query()->where('event_type', 'pilot_readiness_checked')->exists())->toBeTrue();
});
