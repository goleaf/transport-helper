<?php

use App\Enums\PilotFileType;
use App\Models\PilotFile;
use App\Models\PilotSupplier;
use App\Models\User;
use App\Services\Supply\Pilot\PilotMappingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('saves and validates pilot mappings', function (): void {
    $pilot = PilotSupplier::factory()->create();
    $user = User::factory()->create(['role' => 'admin']);
    $file = PilotFile::factory()->for($pilot)->create(['file_type' => PilotFileType::SalesHistorySample->value]);
    $service = app(PilotMappingService::class);

    $service->saveImportMapping($pilot, 'sales_history_sample', [
        'file_id' => $file->id,
        'columns' => ['sku' => 'SKU', 'sales_date' => 'Date', 'quantity' => 'Qty'],
    ], $user);
    $service->saveImportMapping($pilot->fresh(), 'stock_snapshot_sample', [
        'columns' => ['sku' => 'SKU', 'snapshot_date' => 'Date', 'quantity' => 'Qty'],
    ], $user);
    $service->saveManufacturerFormMapping($pilot->fresh(), [
        'items' => ['start_row' => 10, 'columns' => ['sku' => 'A', 'ordered_quantity' => 'D']],
    ], $user);
    $service->saveEmailSampleMapping($pilot->fresh(), 'supplier_confirmation', ['order_number' => 'subject'], $user);
    $service->saveCarrierMapping($pilot->fresh(), ['carrier_name' => 'from', 'price' => 'body'], $user);

    $validation = $service->validateMappings($pilot->fresh());

    expect($validation['errors'])->toBe([])
        ->and($pilot->fresh()->carrier_mapping_json['price'])->toBe('body');
});

it('blocks ambiguous or foreign file mappings', function (): void {
    $pilot = PilotSupplier::factory()->create();
    $otherFile = PilotFile::factory()->create();
    $user = User::factory()->create(['role' => 'admin']);
    $service = app(PilotMappingService::class);

    expect(fn () => $service->saveImportMapping($pilot, 'sales_history_sample', [
        'file_id' => $otherFile->id,
        'columns' => ['sku' => 'SKU'],
    ], $user))->toThrow(ValidationException::class);

    $pilot->update([
        'import_mappings_json' => [
            'sales_history_sample' => ['ambiguous' => true, 'columns' => ['sku' => 'SKU']],
        ],
    ]);

    expect(implode("\n", $service->validateMappings($pilot->fresh())['errors']))->toContain('ambiguous');
});
