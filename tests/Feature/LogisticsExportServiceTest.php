<?php

use App\Exceptions\NotConfiguredYetException;
use App\Models\ExportFile;
use App\Services\Supply\Logistics\LogisticsExportService;
use App\Services\Supply\Logistics\LogisticsGoogleSheetsSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Support\LogisticsTestSupport;

uses(RefreshDatabase::class);

it('exports logistics records to csv and creates export record', function () {
    Storage::fake('local');
    $fixture = LogisticsTestSupport::fixture();

    $result = app(LogisticsExportService::class)->exportCsv([
        'status' => 'confirmed',
    ], $fixture['user']);

    expect(Storage::disk('local')->exists($result['export']->stored_path))->toBeTrue()
        ->and($result['content'])->toContain('logistics_record_id')
        ->and($result['content'])->toContain('PO-LOG-1001')
        ->and(ExportFile::query()->where('export_type', 'logistics_csv')->exists())->toBeTrue();
});

it('google sheets sync placeholder throws not configured', function () {
    $fixture = LogisticsTestSupport::fixture();

    app(LogisticsGoogleSheetsSyncService::class)->sync([], $fixture['user']);
})->throws(NotConfiguredYetException::class);
