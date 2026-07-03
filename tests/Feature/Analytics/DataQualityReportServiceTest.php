<?php

use App\Models\Carrier;
use App\Models\Company;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\Supply\Analytics\DataQualityReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\AnalyticsTestSupport;

uses(RefreshDatabase::class);

it('detects missing product rules, contacts and import errors', function (): void {
    $company = Company::factory()->create();
    Product::factory()->for($company)->create();
    Supplier::factory()->for($company)->create();
    Carrier::factory()->for($company)->create();
    AnalyticsTestSupport::fixture();

    $report = app(DataQualityReportService::class)->report();

    expect($report['summary']['critical'])->toBeGreaterThanOrEqual(1)
        ->and(collect($report['issues'])->pluck('key'))->toContain('products_without_supplier_rules')
        ->and(collect($report['issues'])->pluck('key'))->toContain('import_rows_failed');
});
