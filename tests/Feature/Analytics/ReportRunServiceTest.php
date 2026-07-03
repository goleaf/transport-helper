<?php

use App\Models\ReportRun;
use App\Services\Supply\Analytics\ReportRunService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\AnalyticsTestSupport;

uses(RefreshDatabase::class);

it('creates report run records with summary and warnings', function (): void {
    $fixture = AnalyticsTestSupport::fixture();

    $result = app(ReportRunService::class)->run('supplier_performance', [], $fixture['user']);

    expect($result['report_run'])->toBeInstanceOf(ReportRun::class)
        ->and($result['report_run']->status->value)->toBeIn(['completed', 'completed_with_warnings'])
        ->and($result['report_run']->result_summary_json)->not->toBeEmpty();
});
