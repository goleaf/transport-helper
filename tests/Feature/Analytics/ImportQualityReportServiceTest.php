<?php

use App\Services\Supply\Analytics\ImportQualityReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\AnalyticsTestSupport;

uses(RefreshDatabase::class);

it('reports import error rates and top errors', function (): void {
    AnalyticsTestSupport::fixture();

    $report = app(ImportQualityReportService::class)->report();

    expect($report['summary']['import_batches_count'])->toBeGreaterThanOrEqual(1)
        ->and($report['summary']['failed_rows_count'])->toBeGreaterThanOrEqual(1)
        ->and($report['top_error_messages'][0]['message'])->toContain('unknown SKU');
});
