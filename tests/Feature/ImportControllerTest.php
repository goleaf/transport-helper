<?php

use App\Models\Company;
use App\Models\Product;
use App\Models\SalesHistory;
use App\Services\Import\ImportBatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

function stage3UploadFile(string $contents): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'stage3-upload-');
    file_put_contents($path, $contents);

    return new UploadedFile($path, 'sales.csv', 'text/csv', null, true);
}

function stage3ControllerCsvFile(array $rows): string
{
    $path = tempnam(sys_get_temp_dir(), 'stage3-controller-import-');
    $handle = fopen($path, 'wb');

    foreach ($rows as $row) {
        fputcsv($handle, $row);
    }

    fclose($handle);

    return $path;
}

it('import index page loads', function () {
    $this->get(route('supply.imports.index'))->assertOk();
});

it('import create page loads', function () {
    Company::factory()->create();

    $this->get(route('supply.imports.create'))->assertOk();
});

it('user can upload sales history csv', function () {
    $company = Company::factory()->create();
    Product::factory()->for($company)->create(['sku' => 'SKU-1001']);

    $response = $this->post(route('supply.imports.store'), [
        'company_id' => $company->getKey(),
        'import_type' => 'sales_history',
        'adapter' => 'csv',
        'delimiter' => ',',
        'has_header' => '1',
        'file' => stage3UploadFile("sku,sales_date,quantity\nSKU-1001,2026-07-01,12\n"),
    ]);

    $response->assertRedirect();
    expect(SalesHistory::query()->count())->toBe(1);
});

it('show page displays failed rows', function () {
    $company = Company::factory()->create();
    $path = stage3ControllerCsvFile([
        ['sku', 'sales_date', 'quantity'],
        ['UNKNOWN', '2026-07-01', '12'],
    ]);
    $batch = app(ImportBatchService::class)->run('sales_history', 'csv', ['file_path' => $path], ['company_id' => $company->getKey()])['batch'];

    $this->get(route('supply.imports.show', $batch))
        ->assertOk()
        ->assertSee('SKU not found');
});

it('rollback route updates the batch', function () {
    $company = Company::factory()->create();
    Product::factory()->for($company)->create(['sku' => 'SKU-1001']);
    $path = stage3ControllerCsvFile([
        ['sku', 'sales_date', 'quantity'],
        ['SKU-1001', '2026-07-01', '12'],
    ]);
    $batch = app(ImportBatchService::class)->run('sales_history', 'csv', ['file_path' => $path], ['company_id' => $company->getKey()])['batch'];

    $this->post(route('supply.imports.rollback', $batch))->assertRedirect();

    expect($batch->refresh()->status->value)->toBe('rolled_back')
        ->and(SalesHistory::query()->count())->toBe(0);
});
