<?php

use App\Exceptions\NotConfiguredYetException;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\InboundOrder;
use App\Models\InboundOrderItem;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\SalesHistory;
use App\Models\StockSnapshot;
use App\Models\Supplier;
use App\Models\SupplierProductRule;
use App\Services\Import\ImportBatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function stage3ImportCsv(array $rows): string
{
    $path = tempnam(sys_get_temp_dir(), 'stage3-import-');
    $handle = fopen($path, 'wb');

    foreach ($rows as $row) {
        fputcsv($handle, $row);
    }

    fclose($handle);

    return $path;
}

function stage3RunImport(string $importType, string $path, Company $company, array $options = []): array
{
    return app(ImportBatchService::class)->run(
        $importType,
        'csv',
        ['file_path' => $path],
        ['company_id' => $company->getKey()] + $options,
    );
}

it('sales history csv import creates records', function () {
    $company = Company::factory()->create();
    Product::factory()->for($company)->create(['sku' => 'SKU-1001']);
    $path = stage3ImportCsv([
        ['sku', 'sales_date', 'quantity'],
        ['SKU-1001', '2026-07-01', '12'],
    ]);

    $result = stage3RunImport('sales_history', $path, $company);
    $batch = $result['batch'];

    expect(ImportBatch::query()->count())->toBe(1)
        ->and(ImportRow::query()->count())->toBe(1)
        ->and(SalesHistory::query()->count())->toBe(1)
        ->and($batch->status->value)->toBe('completed')
        ->and($batch->successful_rows)->toBe(1)
        ->and($batch->failed_rows)->toBe(0)
        ->and($batch->rows->first()->status)->toBe('persisted')
        ->and($batch->rows->first()->related_model_type)->toBe(SalesHistory::class)
        ->and(AuditLog::query()->where('event_type', 'import_completed')->exists())->toBeTrue();
});

it('stock snapshot csv import creates records', function () {
    $company = Company::factory()->create();
    Product::factory()->for($company)->create(['sku' => 'SKU-1001']);
    $path = stage3ImportCsv([
        ['sku', 'snapshot_date', 'free_stock', 'total_stock'],
        ['SKU-1001', '2026-07-01', '70', '100'],
    ]);

    stage3RunImport('stock_snapshot', $path, $company);

    expect(StockSnapshot::query()->count())->toBe(1)
        ->and((float) StockSnapshot::query()->first()->free_stock)->toBe(70.0);
});

it('invalid sku creates failed import row', function () {
    $company = Company::factory()->create();
    $path = stage3ImportCsv([
        ['sku', 'sales_date', 'quantity'],
        ['UNKNOWN', '2026-07-01', '12'],
    ]);

    $batch = stage3RunImport('sales_history', $path, $company)['batch'];

    expect(SalesHistory::query()->count())->toBe(0)
        ->and($batch->rows->first()->status)->toBe('invalid')
        ->and($batch->rows->first()->error_message)->toContain('SKU not found')
        ->and($batch->failed_rows)->toBe(1)
        ->and($batch->status->value)->toBe('failed');
});

it('dry run does not persist domain records', function () {
    $company = Company::factory()->create();
    Product::factory()->for($company)->create(['sku' => 'SKU-1001']);
    $path = stage3ImportCsv([
        ['sku', 'sales_date', 'quantity'],
        ['SKU-1001', '2026-07-01', '12'],
    ]);

    $batch = stage3RunImport('sales_history', $path, $company, ['dry_run' => true])['batch'];

    expect($batch->status->value)->toBe('dry_run')
        ->and($batch->rows->first()->status)->toBe('valid')
        ->and(SalesHistory::query()->count())->toBe(0)
        ->and($batch->successful_rows)->toBe(1);
});

it('duplicate file checksum is blocked by default', function () {
    $company = Company::factory()->create();
    Product::factory()->for($company)->create(['sku' => 'SKU-1001']);
    $path = stage3ImportCsv([
        ['sku', 'sales_date', 'quantity'],
        ['SKU-1001', '2026-07-01', '12'],
    ]);

    stage3RunImport('sales_history', $path, $company);
    $second = stage3RunImport('sales_history', $path, $company)['batch'];

    expect($second->status->value)->toBe('failed')
        ->and($second->error_summary)->toBe('duplicate_import_checksum')
        ->and(SalesHistory::query()->count())->toBe(1)
        ->and(AuditLog::query()->where('event_type', 'import_duplicate_blocked')->exists())->toBeTrue();
});

it('duplicate file is allowed when option is true', function () {
    $company = Company::factory()->create();
    Product::factory()->for($company)->create(['sku' => 'SKU-1001']);
    $path = stage3ImportCsv([
        ['sku', 'sales_date', 'quantity'],
        ['SKU-1001', '2026-07-01', '12'],
    ]);

    stage3RunImport('sales_history', $path, $company);
    stage3RunImport('sales_history', $path, $company, ['allow_duplicate' => true]);

    expect(SalesHistory::query()->count())->toBe(2);
});

it('import batch summary counts mixed rows', function () {
    $company = Company::factory()->create();
    Product::factory()->for($company)->create(['sku' => 'SKU-1001']);
    $path = stage3ImportCsv([
        ['sku', 'sales_date', 'quantity'],
        ['SKU-1001', '2026-07-01', '12'],
        ['SKU-1001', '2026-07-02', '5'],
        ['UNKNOWN', '2026-07-03', '3'],
    ]);

    $batch = stage3RunImport('sales_history', $path, $company)['batch'];

    expect($batch->total_rows)->toBe(3)
        ->and($batch->successful_rows)->toBe(2)
        ->and($batch->failed_rows)->toBe(1)
        ->and($batch->status->value)->toBe('completed_with_errors');
});

it('product rules import updates supplier product rule', function () {
    $company = Company::factory()->create();
    $supplier = Supplier::factory()->for($company)->create();
    Product::factory()->for($company)->create(['sku' => 'SKU-1001']);
    $path = stage3ImportCsv([
        ['sku', 'supplier_sku', 'moq', 'pack_multiple', 'pallet_quantity', 'lead_time_days', 'safety_days', 'order_enabled'],
        ['SKU-1001', 'SUP-1001', '24', '12', '144', '21', '14', 'yes'],
    ]);

    stage3RunImport('product_rules', $path, $company, ['supplier_id' => $supplier->getKey()]);

    $rule = SupplierProductRule::query()->firstOrFail();
    expect($rule->supplier_sku)->toBe('SUP-1001')
        ->and((float) $rule->moq)->toBe(24.0);
});

it('reservations import creates records', function () {
    $company = Company::factory()->create();
    Product::factory()->for($company)->create(['sku' => 'SKU-1001']);
    $path = stage3ImportCsv([
        ['sku', 'quantity', 'project_name', 'reserved_at', 'expected_usage_date', 'status'],
        ['SKU-1001', '24', 'Project A', '2026-07-01', '2026-08-01', 'active'],
    ]);

    stage3RunImport('reservations', $path, $company);

    expect(Reservation::query()->count())->toBe(1)
        ->and(Reservation::query()->first()->project_name)->toBe('Project A');
});

it('inbound orders import creates order and item', function () {
    $company = Company::factory()->create();
    $supplier = Supplier::factory()->for($company)->create();
    Product::factory()->for($company)->create(['sku' => 'SKU-1001']);
    $path = stage3ImportCsv([
        ['order_number', 'sku', 'ordered_quantity', 'expected_arrival_date', 'status'],
        ['PO-1001', 'SKU-1001', '120', '2026-07-15', 'ordered'],
    ]);

    stage3RunImport('inbound_orders', $path, $company, ['supplier_id' => $supplier->getKey()]);

    expect(InboundOrder::query()->count())->toBe(1)
        ->and(InboundOrderItem::query()->count())->toBe(1);
});

it('rollback deletes safe imported records', function () {
    $company = Company::factory()->create();
    Product::factory()->for($company)->create(['sku' => 'SKU-1001']);
    $path = stage3ImportCsv([
        ['sku', 'sales_date', 'quantity'],
        ['SKU-1001', '2026-07-01', '12'],
    ]);
    $batch = stage3RunImport('sales_history', $path, $company)['batch'];

    $result = app(ImportBatchService::class)->rollback($batch);

    expect($result['rolled_back_count'])->toBe(1)
        ->and(SalesHistory::query()->count())->toBe(0)
        ->and($batch->refresh()->status->value)->toBe('rolled_back')
        ->and(AuditLog::query()->where('event_type', 'import_rolled_back')->exists())->toBeTrue();
});

it('product rules rollback skips unsafe updates', function () {
    $company = Company::factory()->create();
    $supplier = Supplier::factory()->for($company)->create();
    Product::factory()->for($company)->create(['sku' => 'SKU-1001']);
    $path = stage3ImportCsv([
        ['sku', 'pack_multiple'],
        ['SKU-1001', '12'],
    ]);
    $batch = stage3RunImport('product_rules', $path, $company, ['supplier_id' => $supplier->getKey()])['batch'];

    $result = app(ImportBatchService::class)->rollback($batch);

    expect(SupplierProductRule::query()->count())->toBe(1)
        ->and($result['skipped_count'])->toBe(1)
        ->and($result['skipped_reasons'])->toContain('unsafe_product_rule_rollback');
});

it('placeholder google sheets adapter throws not configured', function () {
    $company = Company::factory()->create();

    app(ImportBatchService::class)->run(
        'sales_history',
        'google_sheets',
        [],
        ['company_id' => $company->getKey()],
    );
})->throws(NotConfiguredYetException::class, 'Integration or adapter [google_sheets] is not configured yet.');
