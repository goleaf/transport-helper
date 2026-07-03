<?php

use App\Services\Supply\Analytics\EmailAiReviewQualityReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\AnalyticsTestSupport;

uses(RefreshDatabase::class);

it('reports AI review quality without treating AI as authoritative', function (): void {
    AnalyticsTestSupport::fixture();

    $report = app(EmailAiReviewQualityReportService::class)->report();

    expect($report['summary']['total_ai_extractions'])->toBe(1)
        ->and($report['summary']['rejected'])->toBe(1)
        ->and($report['messages'])->toContain('AI suggestions are reviewed by humans and are not authoritative.');
});
