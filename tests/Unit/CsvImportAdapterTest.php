<?php

use App\Services\Import\Adapters\CsvImportAdapter;

function stage3CsvAdapterFile(string $contents): string
{
    $path = tempnam(sys_get_temp_dir(), 'csv-adapter-');
    file_put_contents($path, $contents);

    return $path;
}

it('reads csv with header', function () {
    $path = stage3CsvAdapterFile("sku,sales_date,quantity\nSKU-1001,2026-07-01,12\n");

    $rows = (new CsvImportAdapter)->read(['file_path' => $path]);

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['sku'])->toBe('SKU-1001')
        ->and($rows[0]['sales_date'])->toBe('2026-07-01')
        ->and($rows[0]['quantity'])->toBe('12');
});

it('normalizes headers', function () {
    $path = stage3CsvAdapterFile("Product SKU,Sales Date,Sales Qty\nSKU-1001,2026-07-01,12\n");

    $rows = (new CsvImportAdapter)->read(['file_path' => $path]);

    expect($rows[0])->toHaveKeys(['product_sku', 'sales_date', 'sales_qty']);
});

it('supports semicolon delimiter', function () {
    $path = stage3CsvAdapterFile("sku;sales_date;quantity\nSKU-1001;2026-07-01;12\n");

    $rows = (new CsvImportAdapter)->read(['file_path' => $path, 'delimiter' => ';']);

    expect($rows[0]['sku'])->toBe('SKU-1001')
        ->and($rows[0]['quantity'])->toBe('12');
});

it('ignores empty rows', function () {
    $path = stage3CsvAdapterFile("sku,sales_date,quantity\n\nSKU-1001,2026-07-01,12\n,,\n");

    $rows = (new CsvImportAdapter)->read(['file_path' => $path]);

    expect($rows)->toHaveCount(1);
});

it('removes utf8 bom from first header', function () {
    $path = stage3CsvAdapterFile("\xEF\xBB\xBFsku,sales_date,quantity\nSKU-1001,2026-07-01,12\n");

    $rows = (new CsvImportAdapter)->read(['file_path' => $path]);

    expect($rows[0])->toHaveKey('sku');
});

it('throws for missing file', function () {
    (new CsvImportAdapter)->read(['file_path' => '/missing/import.csv']);
})->throws(RuntimeException::class);

it('applies header map', function () {
    $path = stage3CsvAdapterFile("Product Code,Document Date,Sales Qty\nSKU-1001,2026-07-01,12\n");

    $rows = (new CsvImportAdapter)->read([
        'file_path' => $path,
        'header_map' => [
            'Product Code' => 'sku',
            'Document Date' => 'sales_date',
            'Sales Qty' => 'quantity',
        ],
    ]);

    expect($rows[0])->toHaveKeys(['sku', 'sales_date', 'quantity'])
        ->and($rows[0]['sku'])->toBe('SKU-1001')
        ->and($rows[0]['quantity'])->toBe('12');
});
