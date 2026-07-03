<?php

use App\Enums\CarrierQuoteStatus;
use App\Enums\LogisticsStatus;
use App\Enums\SupplierOrderStatus;
use App\Models\AuditLog;
use App\Models\Carrier;
use App\Models\CarrierContact;
use App\Services\Supply\Confirmations\SupplierConfirmationManualDataService;
use App\Services\Supply\Logistics\LogisticsReceivingService;
use App\Services\Supply\Transport\CarrierQuoteComparisonService;
use App\Services\Supply\Transport\CarrierQuoteManualService;
use App\Services\Supply\Transport\CarrierSelectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\Support\SupplierConfirmationTestSupport;

uses(RefreshDatabase::class);

it('runs the controlled supply workflow from confirmation through receiving', function (): void {
    Mail::fake();
    Queue::fake();
    Storage::fake(config('filesystems.default'));

    $fixture = SupplierConfirmationTestSupport::fixture();

    $confirmationResult = app(SupplierConfirmationManualDataService::class)->applyManual(
        $fixture['supplierOrder'],
        SupplierConfirmationTestSupport::manualData(),
        $fixture['user'],
    );

    $carrier = Carrier::factory()->for($fixture['company'])->create([
        'name' => 'Ready Road',
        'code' => 'READY',
        'reliability_score' => 92,
    ]);
    CarrierContact::factory()->for($carrier)->create(['email' => 'quotes@ready-road.test']);

    $quoteResult = app(CarrierQuoteManualService::class)->createManualQuote([
        'supplier_order_id' => $fixture['supplierOrder']->id,
        'carrier_id' => $carrier->id,
        'price' => 500,
        'currency' => 'EUR',
        'pickup_date' => '2026-07-12',
        'delivery_date' => '2026-07-20',
        'transit_days' => 8,
        'conditions' => 'Standard road trailer',
    ], $fixture['user']);

    $comparison = app(CarrierQuoteComparisonService::class)->compareForOrder(
        $fixture['supplierOrder']->fresh(),
        ['required_delivery_date' => '2026-07-20'],
    );

    expect($comparison['requires_human_selection'])->toBeTrue()
        ->and($fixture['supplierOrder']->carrierQuotes()->where('status', CarrierQuoteStatus::Selected->value)->exists())->toBeFalse();

    $selection = app(CarrierSelectionService::class)->select($quoteResult['quote']->fresh(), $fixture['user'], [
        'confirmation' => true,
    ]);

    $receipt = app(LogisticsReceivingService::class)->recordReceipt($fixture['supplierOrder']->fresh(), [
        'actual_received_date' => '2026-07-21',
        'items' => [[
            'product_id' => $fixture['product']->id,
            'sku' => $fixture['product']->sku,
            'received_quantity' => 156,
            'damaged_quantity' => 0,
            'notes' => 'Received in full.',
        ]],
        'confirm_mismatches' => false,
        'complete_order' => true,
        'notes' => 'Warehouse receipt.',
    ], $fixture['user']);

    expect($confirmationResult['confirmation']->status->value ?? $confirmationResult['confirmation']->status)->toBe('confirmed')
        ->and($selection['quote']->status)->toBe(CarrierQuoteStatus::Selected)
        ->and($receipt['record']->status)->toBe(LogisticsStatus::Completed)
        ->and($fixture['supplierOrder']->fresh()->status)->toBe(SupplierOrderStatus::Completed)
        ->and((float) $fixture['supplierOrderItem']->fresh()->received_quantity)->toBe(156.0)
        ->and(AuditLog::query()->whereIn('event_type', [
            'supplier_confirmation_applied',
            'carrier_quote_created',
            'carrier_selected',
            'goods_receipt_recorded',
        ])->count())->toBeGreaterThanOrEqual(3);

    Mail::assertNothingSent();
});
