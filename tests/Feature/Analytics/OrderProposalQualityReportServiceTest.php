<?php

use App\Services\Supply\Analytics\OrderProposalQualityReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\AnalyticsTestSupport;

uses(RefreshDatabase::class);

it('calculates adjustment rate and top adjustment reasons', function (): void {
    AnalyticsTestSupport::fixture();

    $report = app(OrderProposalQualityReportService::class)->report();

    expect($report['summary']['total_proposal_items'])->toBe(1)
        ->and($report['summary']['adjusted_count'])->toBe(1)
        ->and($report['summary']['adjustment_rate'])->toBe(100.0)
        ->and($report['top_adjustment_reasons'][0]['reason'])->toBe('Pilot demand correction');
});
