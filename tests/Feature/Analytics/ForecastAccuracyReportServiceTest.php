<?php

use App\Services\Supply\Analytics\ForecastAccuracyReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\AnalyticsTestSupport;

uses(RefreshDatabase::class);

it('compares approved quantity to actual sales and reports bias', function (): void {
    AnalyticsTestSupport::fixture();

    $report = app(ForecastAccuracyReportService::class)->report();

    expect($report['rows'])->not->toBeEmpty()
        ->and($report['rows'][0])->toHaveKeys([
            'sku',
            'recommended_quantity',
            'approved_quantity',
            'actual_sales_in_coverage_period',
            'absolute_error',
            'percentage_error',
            'bias',
        ]);
});

it('warns when actual sales are missing', function (): void {
    $report = app(ForecastAccuracyReportService::class)->report();

    expect($report['warnings'])->toContain('Forecast accuracy is unavailable without proposal items and later actual sales.');
});
