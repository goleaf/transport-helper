<?php

use App\Services\Supply\Analytics\SupplierConfirmationMismatchReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\AnalyticsTestSupport;

uses(RefreshDatabase::class);

it('counts quantity mismatches and groups by supplier', function (): void {
    AnalyticsTestSupport::fixture();

    $report = app(SupplierConfirmationMismatchReportService::class)->report();

    expect($report['summary']['quantity_mismatch_count'])->toBeGreaterThanOrEqual(1)
        ->and($report['by_supplier'][0])->toHaveKeys(['supplier', 'mismatch_count'])
        ->and($report['rows'][0]['status'])->toBe('quantity_mismatch');
});
