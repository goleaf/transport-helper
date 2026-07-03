<?php

use App\Models\Company;
use App\Models\Product;
use App\Models\SalesHistory;
use App\Services\Import\ImportBatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function legacyCsvWorkflowFile(array $rows): string
{
    $path = tempnam(sys_get_temp_dir(), 'legacy-supply-import-');
    $handle = fopen($path, 'wb');

    foreach ($rows as $row) {
        fputcsv($handle, $row);
    }

    fclose($handle);

    return $path;
}

it('keeps the legacy import wrapper working for CSV imports', function () {
    $company = Company::factory()->create();
    Product::factory()->for($company)->create(['sku' => 'AX-150']);
    $path = legacyCsvWorkflowFile([
        ['sku', 'sales_date', 'quantity'],
        ['AX-150', '2026-07-01', '12'],
    ]);

    $batch = app(ImportBatchService::class)->import([
        'company_id' => $company->getKey(),
        'import_type' => 'sales_history',
        'adapter' => 'csv',
        'source_path' => $path,
        'original_filename' => 'legacy.csv',
    ]);

    expect($batch->status->value)->toBe('completed')
        ->and($batch->import_type)->toBe('sales_history')
        ->and($batch->source_type)->toBe('csv')
        ->and($batch->rows)->toHaveCount(1)
        ->and($batch->rows->first()->status)->toBe('persisted')
        ->and(SalesHistory::query()->count())->toBe(1);
});
