<?php

use App\Models\ExportFile;
use App\Services\Supply\Analytics\AnalyticsExportService;
use App\Services\Supply\Analytics\SupplierPerformanceReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Support\AnalyticsTestSupport;

uses(RefreshDatabase::class);

it('exports report CSV and JSON without full email bodies', function (): void {
    Storage::fake('local');
    $fixture = AnalyticsTestSupport::fixture();
    $report = app(SupplierPerformanceReportService::class)->report();

    $csv = app(AnalyticsExportService::class)->exportCsv('supplier_performance', $report, [], $fixture['user']);
    $json = app(AnalyticsExportService::class)->exportJson('supplier_performance', $report, [], $fixture['user']);

    expect(ExportFile::query()->count())->toBe(2)
        ->and(Storage::disk('local')->exists($csv['export_file']->stored_path))->toBeTrue()
        ->and(Storage::disk('local')->exists($json['export_file']->stored_path))->toBeTrue()
        ->and(Storage::disk('local')->get($json['export_file']->stored_path))->not->toContain('Full private supplier body');
});
