<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('runs incident commands in safe dry-run/json modes', function (): void {
    $this->artisan('supply:detect-incidents --dry-run --json')
        ->assertExitCode(0);

    $this->artisan('supply:monitor-incident-sla --dry-run --json')
        ->assertExitCode(0);

    $this->artisan('supply:incident-report --json')
        ->assertExitCode(0);

    $this->artisan('supply:incident-health --json')
        ->assertExitCode(0);
});
