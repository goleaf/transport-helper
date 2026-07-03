<?php

use App\Exceptions\NotConfiguredYetException;
use App\Models\SupplierOrder;
use App\Services\Export\SupplierOrders\CsvSupplierOrderExporter;
use App\Services\Export\SupplierOrders\ExcelCsvSupplierOrderExporter;
use App\Services\Export\SupplierOrders\JsonSupplierOrderExporter;
use App\Services\Export\SupplierOrders\PdfSupplierOrderExporterPlaceholder;
use App\Services\Export\SupplierOrders\SupplierCustomTemplateExporterPlaceholder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

require_once __DIR__.'/../Feature/SupplierOrderStage5Support.php';

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Storage::fake(config('filesystems.default'));
});

it('creates expected CSV content', function () {
    $fixture = stage5SupplierOrderFixture();

    $result = app(CsvSupplierOrderExporter::class)->export($fixture['order']);
    $content = Storage::get($result['stored_path']);

    expect($content)->toContain('order_number')
        ->and($content)->toContain('PO-TEST-1')
        ->and($content)->toContain('AX-150')
        ->and($content)->toContain('Axle Bearing 150')
        ->and($content)->toContain('156.000');
});

it('creates expected JSON structure', function () {
    $fixture = stage5SupplierOrderFixture();

    $result = app(JsonSupplierOrderExporter::class)->export($fixture['order'], [
        'generated_by_user_id' => $fixture['user']->id,
    ]);
    $payload = json_decode(Storage::get($result['stored_path']), true, flags: JSON_THROW_ON_ERROR);

    expect($payload)->toHaveKeys(['format_version', 'supplier_order', 'supplier', 'items'])
        ->and($payload['supplier_order']['order_number'])->toBe('PO-TEST-1')
        ->and($payload['items'][0]['sku'])->toBe('AX-150');
});

it('uses semicolon and BOM for Excel CSV', function () {
    $fixture = stage5SupplierOrderFixture();

    $result = app(ExcelCsvSupplierOrderExporter::class)->export($fixture['order']);
    $content = Storage::get($result['stored_path']);

    expect(substr($content, 0, 3))->toBe("\xEF\xBB\xBF")
        ->and($content)->toContain('order_number;order_date')
        ->and($result['filename'])->toBe('PO-TEST-1_excel.csv');
});

it('includes supplier SKU when rule exists', function () {
    $fixture = stage5SupplierOrderFixture();

    $result = app(CsvSupplierOrderExporter::class)->export($fixture['order']);

    expect(Storage::get($result['stored_path']))->toContain('SUP-AX-150');
});

it('throws for PDF placeholder', function () {
    app(PdfSupplierOrderExporterPlaceholder::class)->export(SupplierOrder::factory()->make());
})->throws(NotConfiguredYetException::class);

it('throws for custom template placeholder', function () {
    app(SupplierCustomTemplateExporterPlaceholder::class)->export(SupplierOrder::factory()->make());
})->throws(NotConfiguredYetException::class);
