<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\AnalyticsTestSupport;

uses(RefreshDatabase::class);

it('runs analytics report and export commands', function (): void {
    AnalyticsTestSupport::fixture();

    $this->artisan('supply:analytics-report supplier_performance --format=json')
        ->assertExitCode(0);

    $this->artisan('supply:analytics-report stockout_risk --format=json')
        ->assertExitCode(0);

    $this->artisan('supply:analytics-export logistics_performance --format=json')
        ->assertExitCode(0);
});
