<?php

use App\Exceptions\NotConfiguredYetException;
use App\Models\AuditLog;
use App\Models\ExportFile;
use App\Services\Supply\SupplierOrders\SupplierOrderExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

require_once __DIR__.'/SupplierOrderStage5Support.php';

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake(config('filesystems.default'));
});

it('creates export file record and audit log', function () {
    $fixture = stage5SupplierOrderFixture();

    $result = app(SupplierOrderExportService::class)->export($fixture['order'], 'csv', [], $fixture['user']);

    expect($result['export_file'])->toBeInstanceOf(ExportFile::class)
        ->and(Storage::exists($result['path']))->toBeTrue()
        ->and($result['export_file']->status)->toBe('stored')
        ->and(AuditLog::query()->where('event_type', 'supplier_order_exported')->exists())->toBeTrue()
        ->and(AuditLog::query()->where('event_type', 'export_created')->exists())->toBeTrue();
});

it('blocks order without items', function () {
    $fixture = stage5SupplierOrderFixture();
    $fixture['order']->items()->delete();

    app(SupplierOrderExportService::class)->export($fixture['order'], 'csv', [], $fixture['user']);
})->throws(ValidationException::class);

it('creates JSON export file', function () {
    $fixture = stage5SupplierOrderFixture();

    $result = app(SupplierOrderExportService::class)->export($fixture['order'], 'json', [], $fixture['user']);

    expect($result['filename'])->toBe('PO-TEST-1.json')
        ->and(Storage::get($result['path']))->toContain('"supplier_order"');
});

it('creates Excel CSV export file', function () {
    $fixture = stage5SupplierOrderFixture();

    $result = app(SupplierOrderExportService::class)->export($fixture['order'], 'excel_csv', [], $fixture['user']);

    expect($result['filename'])->toBe('PO-TEST-1_excel.csv')
        ->and(Storage::exists($result['path']))->toBeTrue();
});

it('downloads export file through private route', function () {
    $fixture = stage5SupplierOrderFixture();
    $result = app(SupplierOrderExportService::class)->export($fixture['order'], 'csv', [], $fixture['user']);

    $this->actingAs($fixture['user'])
        ->get(route('supply.exports.download', $result['export_file']))
        ->assertSuccessful();
});

it('throws for PDF placeholder without creating successful export', function () {
    $fixture = stage5SupplierOrderFixture();

    app(SupplierOrderExportService::class)->export($fixture['order'], 'pdf', [], $fixture['user']);
})->throws(NotConfiguredYetException::class);
