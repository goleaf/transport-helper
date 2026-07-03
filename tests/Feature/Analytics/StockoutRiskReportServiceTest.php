<?php

use App\Services\Supply\Analytics\StockoutRiskReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\AnalyticsTestSupport;

uses(RefreshDatabase::class);

it('marks zero stock with positive sales velocity as critical risk', function (): void {
    AnalyticsTestSupport::fixture();

    $report = app(StockoutRiskReportService::class)->report();

    expect($report['summary']['critical_count'])->toBeGreaterThanOrEqual(1)
        ->and($report['rows'][0]['risk_level'])->toBe('critical')
        ->and($report['rows'][0]['recommended_action'])->not->toBeEmpty();
});

it('reports unknown data when stock snapshot is missing', function (): void {
    $report = app(StockoutRiskReportService::class)->report();

    expect($report['warnings'])->toContain('No stock snapshots found for the selected period.');
});
