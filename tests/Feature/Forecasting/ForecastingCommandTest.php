<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ForecastingTestSupport;

uses(RefreshDatabase::class);

it('run scenario command runs with required options', function (): void {
    $fixture = ForecastingTestSupport::fixture();
    ForecastingTestSupport::seedCalculationSales($fixture['company'], $fixture['product']);

    $this->artisan('supply:run-scenario', [
        '--company_id' => $fixture['company']->id,
        '--supplier_id' => $fixture['supplier']->id,
        '--name' => 'Command scenario',
        '--t0' => '2026-07-01',
        '--t1' => '2026-07-15',
        '--t2' => '2026-08-14',
        '--t3' => '2026-09-01',
    ])->assertExitCode(0);
});

it('forecast refinement audit command runs', function (): void {
    ForecastingTestSupport::fixture();

    $this->artisan('supply:forecast-refinement-audit')
        ->assertExitCode(0);
});

it('forecast refinement audit json output runs', function (): void {
    ForecastingTestSupport::fixture();

    $this->artisan('supply:forecast-refinement-audit', ['--json' => true])
        ->expectsOutputToContain('forecasting_boundary')
        ->assertExitCode(0);
});
