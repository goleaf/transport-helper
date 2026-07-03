<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\LogisticsTestSupport;

uses(RefreshDatabase::class);

it('health check command runs', function () {
    LogisticsTestSupport::fixture();

    $this->artisan('supply:health-check')
        ->assertExitCode(0);
});

it('health check command supports json output', function () {
    LogisticsTestSupport::fixture();

    $this->artisan('supply:health-check --json')
        ->expectsOutputToContain('"status"')
        ->assertExitCode(0);
});
