<?php

use App\Services\Supply\Analytics\TransportPerformanceReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\AnalyticsTestSupport;

uses(RefreshDatabase::class);

it('reports selected carrier frequency and non-lowest selection count', function (): void {
    AnalyticsTestSupport::fixture();

    $report = app(TransportPerformanceReportService::class)->report();

    expect($report['summary']['selected_quotes'])->toBe(1)
        ->and($report['summary']['non_lowest_selected_due_to_date_or_reliability'])->toBe(1)
        ->and($report['messages'])->toContain('Lowest price is not automatically treated as the best carrier choice.');
});
