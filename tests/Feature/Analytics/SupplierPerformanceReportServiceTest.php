<?php

use App\Services\Supply\Analytics\SupplierPerformanceReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\AnalyticsTestSupport;

uses(RefreshDatabase::class);

it('calculates supplier confirmation and quantity match metrics', function (): void {
    AnalyticsTestSupport::fixture();

    $report = app(SupplierPerformanceReportService::class)->report();

    expect($report['summary']['total_supplier_orders'])->toBeGreaterThanOrEqual(1)
        ->and($report['summary']['confirmation_rate'])->toBeGreaterThan(0)
        ->and($report['summary']['quantity_match_rate'])->toBeLessThan(100)
        ->and($report['rows'][0])->toHaveKeys(['supplier', 'risk_level', 'mismatch_count']);
});
