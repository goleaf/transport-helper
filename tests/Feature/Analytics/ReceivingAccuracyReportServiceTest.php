<?php

use App\Services\Supply\Analytics\ReceivingAccuracyReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\AnalyticsTestSupport;

uses(RefreshDatabase::class);

it('reports receiving mismatches and damaged quantities', function (): void {
    AnalyticsTestSupport::fixture();

    $report = app(ReceivingAccuracyReportService::class)->report();

    expect($report['summary']['received_orders_count'])->toBe(1)
        ->and($report['summary']['receiving_mismatches'])->toBe(1)
        ->and($report['summary']['damaged_quantity_count'])->toBe(2.0);
});
