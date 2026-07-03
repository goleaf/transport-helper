<?php

use App\Enums\PilotFileType;
use App\Models\Carrier;
use App\Models\CarrierContact;
use App\Models\PilotFile;
use App\Models\PilotSupplier;
use App\Models\Product;
use App\Models\SupplierContact;
use App\Models\SupplierProductRule;
use App\Services\Supply\Pilot\PilotDataQualityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('fails when required files are missing', function (): void {
    $pilot = PilotSupplier::factory()->create();

    $result = app(PilotDataQualityService::class)->analyze($pilot);

    expect($result['status'])->toBe('failed')
        ->and($result['errors'])->not->toBeEmpty();
});

it('detects invalid sample data and unknown sku warnings', function (): void {
    Storage::fake('local');
    $pilot = PilotSupplier::factory()->create();
    Product::factory()->for($pilot->company)->create(['sku' => 'KNOWN']);
    SupplierProductRule::factory()->for($pilot->supplier)->create(['product_id' => Product::factory()->for($pilot->company)->create()->id]);
    SupplierContact::factory()->for($pilot->supplier)->create(['receives_orders' => true]);
    CarrierContact::factory()->for(Carrier::factory()->for($pilot->company)->create())->create();
    Storage::disk('local')->put('pilot/test/sales.csv', "SKU,Date,Qty\nUNKNOWN,2026-99-99,nope\n");
    $file = PilotFile::factory()->create([
        'pilot_supplier_id' => $pilot->id,
        'file_type' => PilotFileType::SalesHistorySample->value,
        'stored_path' => 'pilot/test/sales.csv',
        'original_filename' => 'sales.csv',
    ]);
    $pilot->update([
        'import_mappings_json' => [
            'sales_history_sample' => [
                'file_id' => $file->id,
                'columns' => ['sku' => 'SKU', 'sales_date' => 'Date', 'quantity' => 'Qty'],
            ],
        ],
    ]);

    $result = app(PilotDataQualityService::class)->analyze($pilot->fresh());

    expect(implode("\n", $result['errors']))->toContain('invalid dates')
        ->and(implode("\n", $result['warnings']))->toContain('unknown SKUs');
});
