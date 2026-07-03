<?php

use App\Enums\CarrierQuoteStatus;
use App\Models\CarrierQuote;
use App\Services\Supply\Transport\CarrierQuoteApplicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Support\TransportTestSupport;

uses(RefreshDatabase::class);

it('creates manual carrier quote without selecting carrier or updating logistics', function () {
    $fixture = TransportTestSupport::fixture();

    $result = app(CarrierQuoteApplicationService::class)->createQuote([
        'source_type' => 'manual',
        'supplier_order_id' => $fixture['supplierOrder']->id,
        'carrier_id' => $fixture['carrier']->id,
        'price' => 500,
        'currency' => 'EUR',
        'pickup_date' => '2026-07-10',
        'delivery_date' => '2026-07-20',
    ], $fixture['user']);

    expect($result['quote']->status)->toBe(CarrierQuoteStatus::Received)
        ->and($result['quote']->selected_at)->toBeNull()
        ->and($fixture['logisticsRecord']->refresh()->carrier_id)->toBeNull();
});

it('saves quote with missing delivery date as needs review', function () {
    $fixture = TransportTestSupport::fixture();

    $result = app(CarrierQuoteApplicationService::class)->createQuote([
        'source_type' => 'manual',
        'supplier_order_id' => $fixture['supplierOrder']->id,
        'carrier_id' => $fixture['carrier']->id,
        'price' => 500,
        'currency' => 'EUR',
    ], $fixture['user']);

    expect($result['quote']->status)->toBe(CarrierQuoteStatus::NeedsReview)
        ->and($result['quote']->warnings_json)->toContain('missing_delivery_date');
});

it('blocks duplicate source application', function () {
    $fixture = TransportTestSupport::fixture();
    $payload = [
        'source_type' => 'ai_email_extraction',
        'source_id' => 77,
        'supplier_order_id' => $fixture['supplierOrder']->id,
        'carrier_id' => $fixture['carrier']->id,
        'price' => 500,
        'currency' => 'EUR',
        'delivery_date' => '2026-07-20',
    ];

    app(CarrierQuoteApplicationService::class)->createQuote($payload, $fixture['user']);
    app(CarrierQuoteApplicationService::class)->createQuote($payload, $fixture['user']);
})->throws(ValidationException::class);

it('quote application does not select carrier', function () {
    $fixture = TransportTestSupport::fixture();

    app(CarrierQuoteApplicationService::class)->createQuote([
        'source_type' => 'manual',
        'supplier_order_id' => $fixture['supplierOrder']->id,
        'carrier_id' => $fixture['carrier']->id,
        'price' => 500,
        'currency' => 'EUR',
        'delivery_date' => '2026-07-20',
    ], $fixture['user']);

    expect(CarrierQuote::query()->where('status', 'selected')->exists())->toBeFalse();
});
