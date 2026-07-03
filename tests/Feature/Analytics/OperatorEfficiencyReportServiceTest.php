<?php

use App\Services\Supply\Analytics\OperatorEfficiencyReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\AnalyticsTestSupport;

uses(RefreshDatabase::class);

it('reports workflow cycle times and bottleneck stages', function (): void {
    AnalyticsTestSupport::fixture();

    $report = app(OperatorEfficiencyReportService::class)->report();

    expect($report['summary'])->toHaveKeys([
        'average_time_proposal_created_to_approved_hours',
        'average_time_email_prepared_to_sent_hours',
        'average_time_quote_received_to_carrier_selected_hours',
        'bottleneck_stages',
    ]);
});
