<?php

use App\Services\Supply\Analytics\AuditKpiReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\AnalyticsTestSupport;

uses(RefreshDatabase::class);

it('counts audit events by type and does not expose secrets', function (): void {
    AnalyticsTestSupport::fixture();

    $report = app(AuditKpiReportService::class)->report();
    $encoded = json_encode($report);

    expect($report['summary']['total_events'])->toBeGreaterThanOrEqual(2)
        ->and($report['events_by_type'])->not->toBeEmpty()
        ->and($report)->toHaveKey('critical_event_list')
        ->and($encoded)->not->toContain('password')
        ->and($encoded)->not->toContain('secret');
});
