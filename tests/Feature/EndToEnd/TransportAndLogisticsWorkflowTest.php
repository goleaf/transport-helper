<?php

use App\Enums\CarrierQuoteStatus;
use App\Enums\LogisticsStatus;
use App\Services\Supply\Logistics\LogisticsReceivingService;
use App\Services\Supply\Transport\CarrierQuoteComparisonService;
use App\Services\Supply\Transport\CarrierQuoteFromAiExtractionService;
use App\Services\Supply\Transport\CarrierQuoteFromFormAutofillService;
use App\Services\Supply\Transport\CarrierSelectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\LogisticsTestSupport;
use Tests\Support\TransportTestSupport;

uses(RefreshDatabase::class);

it('lowest price with late delivery does not auto win or auto select', function (): void {
    $fixture = TransportTestSupport::fixture();
    $onTime = TransportTestSupport::quote($fixture, ['price' => 500, 'delivery_date' => '2026-07-20']);
    $lateCheap = TransportTestSupport::quote($fixture, [
        'carrier_id' => $fixture['lateCarrier']->id,
        'price' => 400,
        'delivery_date' => '2026-07-30',
    ]);

    $comparison = app(CarrierQuoteComparisonService::class)->compareForOrder($fixture['supplierOrder'], [
        'required_delivery_date' => '2026-07-20',
    ]);

    expect($comparison['best_quote_id'])->toBe($onTime->id)
        ->and($lateCheap->fresh()->status)->toBe(CarrierQuoteStatus::Received)
        ->and($fixture['supplierOrder']->carrierQuotes()->where('status', CarrierQuoteStatus::Selected->value)->exists())->toBeFalse();
});

it('user must select carrier before logistics receives selected carrier data', function (): void {
    $fixture = TransportTestSupport::fixture();
    $quote = TransportTestSupport::quote($fixture);

    app(CarrierQuoteComparisonService::class)->compareForOrder($fixture['supplierOrder']);

    expect($fixture['logisticsRecord']->fresh()->carrier_id)->toBeNull();

    $selection = app(CarrierSelectionService::class)->select($quote, $fixture['user'], ['confirmation' => true]);

    expect($selection['logistics_record']->carrier_id)->toBe($fixture['carrier']->id);
});

it('ai created carrier quote is not selected automatically', function (): void {
    $fixture = TransportTestSupport::fixture();
    $extraction = TransportTestSupport::acceptedAiExtraction($fixture);

    $result = app(CarrierQuoteFromAiExtractionService::class)->apply($extraction, $fixture['user']);

    expect($result['quote']->status)->toBe(CarrierQuoteStatus::Received)
        ->and($result['quote']->selected_at)->toBeNull();
});

it('form autofill carrier quote is not selected automatically', function (): void {
    $fixture = TransportTestSupport::fixture();
    $run = TransportTestSupport::carrierQuoteFormRun($fixture);

    $result = app(CarrierQuoteFromFormAutofillService::class)->apply($run, $fixture['user']);

    expect($result['quote']->status)->toBe(CarrierQuoteStatus::Received)
        ->and($result['quote']->selected_at)->toBeNull();
});

it('receiving mismatch marks logistics needs review', function (): void {
    $fixture = LogisticsTestSupport::fixture();

    $result = app(LogisticsReceivingService::class)->recordReceipt($fixture['supplierOrder'], [
        'actual_received_date' => '2026-07-21',
        'items' => [[
            'product_id' => $fixture['product']->id,
            'received_quantity' => 150,
            'damaged_quantity' => 0,
        ]],
        'confirm_mismatches' => true,
        'complete_order' => true,
        'notes' => 'Short receipt accepted for review.',
    ], $fixture['user']);

    expect($result['record']->status)->toBe(LogisticsStatus::NeedsReview);
});
