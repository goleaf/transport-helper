<?php

use App\Services\Supply\Analytics\FormAutofillQualityReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\AnalyticsTestSupport;

uses(RefreshDatabase::class);

it('reports form autofill correction rates and low confidence fields', function (): void {
    AnalyticsTestSupport::fixture();

    $report = app(FormAutofillQualityReportService::class)->report();

    expect($report['summary']['total_autofill_runs'])->toBe(1)
        ->and($report['summary']['fields_edited'])->toBe(1)
        ->and($report['summary']['low_confidence_fields'])->toBe(1);
});
