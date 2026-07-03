<?php

use App\Models\PilotSupplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('runs pilot commands', function (): void {
    $user = User::factory()->create(['role' => 'admin']);
    $pilot = PilotSupplier::factory()->create(['created_by_user_id' => $user->id]);

    $this->artisan('supply:pilot-onboarding-checklist --json')->assertExitCode(0);
    $this->artisan('supply:pilot-readiness '.$pilot->id.' --json')->assertExitCode(0);
    $this->artisan('supply:pilot-dry-run '.$pilot->id.' full_uat_dry_run --json')->assertExitCode(0);
    $this->artisan('supply:pilot-uat-report '.$pilot->id.' --json')->assertExitCode(0);
});
