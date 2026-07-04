<?php

use App\Models\Product;
use App\Models\UnknownSkuResolution;
use App\Services\Supply\MasterData\MasterDataQualityReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MasterDataTestSupport;

uses(RefreshDatabase::class);

it('reports quality metrics and exports csv without mutating business data', function (): void {
    $fixture = MasterDataTestSupport::fixture();
    Product::factory()->for($fixture['company'])->create(['manufacturer_sku' => null]);
    UnknownSkuResolution::factory()->for($fixture['company'])->create(['status' => 'unresolved']);
    $service = app(MasterDataQualityReportService::class);

    $report = $service->report($fixture['company']);
    $export = $service->exportCsv($fixture['company'], ['format' => 'csv'], $fixture['admin']);

    expect($report['summary']['unresolved_unknown_sku_count'])->toBe(1)
        ->and($report['issues'])->not->toBeEmpty()
        ->and($export['export']->export_type)->toBe('master_data_quality_csv')
        ->and(Product::query()->where('company_id', $fixture['company']->id)->count())->toBe(2);
});
