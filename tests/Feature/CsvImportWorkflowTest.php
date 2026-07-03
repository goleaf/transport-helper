<?php

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\ImportBatch;
use App\Models\Product;
use App\Models\SalesHistory;
use App\Models\StockSnapshot;
use App\Services\Import\ImportBatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeImportCsv(array $rows): string
{
    $path = tempnam(sys_get_temp_dir(), 'supply-import-');
    $handle = fopen($path, 'wb');

    foreach ($rows as $row) {
        fputcsv($handle, $row);
    }

    fclose($handle);

    return $path;
}

function runCsvImport(array $overrides): ImportBatch
{
    return app(ImportBatchService::class)->import(array_merge([
        'adapter' => 'csv',
        'original_filename' => 'import.csv',
        'source_reference' => 'test-source',
        'dry_run' => false,
    ], $overrides));
}

it('CSV sales import creates sales_history', function () {
    $company = Company::factory()->create();
    $product = Product::factory()->for($company)->create(['sku' => 'AX-150']);
    $path = makeImportCsv([
        ['sku', 'sales_date', 'quantity', 'channel'],
        ['AX-150', '2026-07-01', '12', 'b2b'],
    ]);

    $batch = runCsvImport([
        'company_id' => $company->getKey(),
        'import_type' => 'sales_history',
        'source_path' => $path,
    ]);

    $sale = SalesHistory::query()
        ->whereBelongsTo($company)
        ->whereBelongsTo($product)
        ->firstOrFail();

    $this->assertModelExists($sale);
    expect($batch->status->value)->toBe('completed')
        ->and($batch->total_rows)->toBe(1)
        ->and($batch->successful_rows)->toBe(1)
        ->and($batch->failed_rows)->toBe(0)
        ->and((float) $sale->quantity)->toBe(12.0)
        ->and($sale->sales_date->toDateString())->toBe('2026-07-01')
        ->and($sale->importBatch->is($batch))->toBeTrue()
        ->and($batch->rows)->toHaveCount(1)
        ->and($batch->rows->first()->related_model_type)->toBe(SalesHistory::class)
        ->and($batch->rows->first()->related_model_id)->toBe($sale->getKey());
});

it('CSV stock import creates stock_snapshots', function () {
    $company = Company::factory()->create();
    $product = Product::factory()->for($company)->create(['sku' => 'AX-150']);
    $path = makeImportCsv([
        ['sku', 'snapshot_date', 'free_stock', 'total_stock'],
        ['AX-150', '2026-07-02', '70', '95'],
    ]);

    $batch = runCsvImport([
        'company_id' => $company->getKey(),
        'import_type' => 'stock_snapshot',
        'source_path' => $path,
    ]);

    $snapshot = StockSnapshot::query()
        ->whereBelongsTo($company)
        ->whereBelongsTo($product)
        ->firstOrFail();

    $this->assertModelExists($snapshot);
    expect($batch->status->value)->toBe('completed')
        ->and((float) $snapshot->free_stock)->toBe(70.0)
        ->and((float) $snapshot->total_stock)->toBe(95.0)
        ->and($snapshot->snapshot_date->toDateString())->toBe('2026-07-02')
        ->and($snapshot->importBatch->is($batch))->toBeTrue()
        ->and($batch->rows->first()->related_model_type)->toBe(StockSnapshot::class)
        ->and($batch->rows->first()->related_model_id)->toBe($snapshot->getKey());
});

it('invalid SKU creates failed import row', function () {
    $company = Company::factory()->create();
    $path = makeImportCsv([
        ['sku', 'sales_date', 'quantity'],
        ['MISSING-SKU', '2026-07-01', '12'],
    ]);

    $batch = runCsvImport([
        'company_id' => $company->getKey(),
        'import_type' => 'sales_history',
        'source_path' => $path,
    ]);

    $row = $batch->rows->first();

    expect(SalesHistory::query()->count())->toBe(0)
        ->and($batch->status->value)->toBe('completed_with_errors')
        ->and($batch->successful_rows)->toBe(0)
        ->and($batch->failed_rows)->toBe(1)
        ->and($row->status)->toBe('failed')
        ->and($row->raw_json)->toMatchArray(['sku' => 'MISSING-SKU'])
        ->and($row->normalized_json)->toHaveKey('sku')
        ->and($row->error_message)->toContain('Unknown SKU [MISSING-SKU]');
});

it('dry-run does not persist domain records', function () {
    $company = Company::factory()->create();
    Product::factory()->for($company)->create(['sku' => 'AX-150']);
    $path = makeImportCsv([
        ['sku', 'sales_date', 'quantity'],
        ['AX-150', '2026-07-01', '12'],
    ]);

    $batch = runCsvImport([
        'company_id' => $company->getKey(),
        'import_type' => 'sales_history',
        'source_path' => $path,
        'dry_run' => true,
    ]);

    $row = $batch->rows->first();

    expect(SalesHistory::query()->count())->toBe(0)
        ->and($batch->status->value)->toBe('dry_run')
        ->and($batch->successful_rows)->toBe(1)
        ->and($batch->failed_rows)->toBe(0)
        ->and($row->status)->toBe('dry_run')
        ->and($row->related_model_type)->toBeNull()
        ->and($row->related_model_id)->toBeNull();
});

it('duplicate file warning works', function () {
    $company = Company::factory()->create();
    Product::factory()->for($company)->create(['sku' => 'AX-150']);
    $path = makeImportCsv([
        ['sku', 'sales_date', 'quantity'],
        ['AX-150', '2026-07-01', '12'],
    ]);
    $payload = [
        'company_id' => $company->getKey(),
        'import_type' => 'sales_history',
        'source_path' => $path,
        'source_reference' => 'duplicate-source',
    ];

    runCsvImport($payload);
    $secondBatch = runCsvImport($payload);

    expect($secondBatch->error_summary)->toContain('duplicate_file_warning')
        ->and($secondBatch->status->value)->toBe('completed')
        ->and($secondBatch->successful_rows)->toBe(1);
});

it('import creates audit log', function () {
    $company = Company::factory()->create();
    Product::factory()->for($company)->create(['sku' => 'AX-150']);
    $path = makeImportCsv([
        ['sku', 'sales_date', 'quantity'],
        ['AX-150', '2026-07-01', '12'],
    ]);

    $batch = runCsvImport([
        'company_id' => $company->getKey(),
        'import_type' => 'sales_history',
        'source_path' => $path,
    ]);

    $auditLog = AuditLog::query()
        ->where('event_type', 'import_batch.created')
        ->firstOrFail();

    expect($auditLog->auditable->is($batch))->toBeTrue()
        ->and($auditLog->company->is($company))->toBeTrue()
        ->and($auditLog->metadata_json)->toMatchArray([
            'import_type' => 'sales_history',
            'adapter' => 'csv',
            'dry_run' => false,
            'duplicate_file' => false,
            'total_rows' => 1,
            'successful_rows' => 1,
            'failed_rows' => 0,
        ]);
});

it('import batch summary counts rows correctly', function () {
    $company = Company::factory()->create();
    Product::factory()->for($company)->create(['sku' => 'AX-150']);
    $path = makeImportCsv([
        ['sku', 'sales_date', 'quantity'],
        ['AX-150', '2026-07-01', '12'],
        ['MISSING-SKU', '2026-07-02', '5'],
    ]);

    $batch = runCsvImport([
        'company_id' => $company->getKey(),
        'import_type' => 'sales_history',
        'source_path' => $path,
    ]);

    expect($batch->status->value)->toBe('completed_with_errors')
        ->and($batch->total_rows)->toBe(2)
        ->and($batch->successful_rows)->toBe(1)
        ->and($batch->failed_rows)->toBe(1)
        ->and($batch->rows)->toHaveCount(2)
        ->and($batch->error_summary)->toContain('1 row(s) failed validation')
        ->and(SalesHistory::query()->count())->toBe(1);
});
