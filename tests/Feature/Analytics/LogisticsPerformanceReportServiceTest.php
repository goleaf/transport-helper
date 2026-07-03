<?php

use App\Services\Supply\Analytics\LogisticsPerformanceReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\AnalyticsTestSupport;

uses(RefreshDatabase::class);

it('calculates delay rate and average stage durations', function (): void {
    AnalyticsTestSupport::fixture();

    $report = app(LogisticsPerformanceReportService::class)->report();

    expect($report['summary']['delayed_records'])->toBe(1)
        ->and($report['summary']['delay_rate'])->toBe(100.0)
        ->and($report['summary'])->toHaveKeys([
            'average_days_order_to_confirmation',
            'average_days_confirmation_to_ready',
            'missing_ready_date_count',
        ]);
});
