<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\LogisticsTestSupport;

uses(RefreshDatabase::class);

it('command runs successfully in dry run mode', function () {
    LogisticsTestSupport::fixture();

    $this->artisan('supply:monitor-logistics --dry-run')
        ->assertSuccessful();
});

it('command supports json output', function () {
    LogisticsTestSupport::fixture();

    $this->artisan('supply:monitor-logistics --dry-run --json')
        ->expectsOutputToContain('"dry_run":true')
        ->assertSuccessful();
});
