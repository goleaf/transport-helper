<?php

use App\Services\Supply\Transport\CarrierQuoteValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\TransportTestSupport;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('passes a valid quote and resolves carrier name', function () {
    $fixture = TransportTestSupport::fixture();

    $result = app(CarrierQuoteValidationService::class)->validate([
        'carrier_name' => 'Fast Road',
        'price' => 500,
        'currency' => 'EUR',
        'pickup_date' => '2026-07-10',
        'delivery_date' => '2026-07-20',
        'transit_days' => 10,
    ], ['company_id' => $fixture['company']->id]);

    expect($result['valid'])->toBeTrue()
        ->and($result['status'])->toBe('received')
        ->and($result['normalized']['carrier_id'])->toBe($fixture['carrier']->id);
});

it('marks missing price and delivery date as needs review', function () {
    $fixture = TransportTestSupport::fixture();

    $result = app(CarrierQuoteValidationService::class)->validate([
        'carrier_id' => $fixture['carrier']->id,
    ], ['company_id' => $fixture['company']->id]);

    expect($result['valid'])->toBeTrue()
        ->and($result['status'])->toBe('needs_review')
        ->and($result['warnings'])->toContain('missing_price')
        ->and($result['warnings'])->toContain('missing_delivery_date');
});

it('rejects delivery before pickup', function () {
    $fixture = TransportTestSupport::fixture();

    $result = app(CarrierQuoteValidationService::class)->validate([
        'carrier_id' => $fixture['carrier']->id,
        'price' => 500,
        'currency' => 'EUR',
        'pickup_date' => '2026-07-20',
        'delivery_date' => '2026-07-10',
    ], ['company_id' => $fixture['company']->id]);

    expect($result['valid'])->toBeFalse()
        ->and($result['errors'])->toContain('invalid_date_order');
});

it('adds late delivery and zero price warnings', function () {
    $fixture = TransportTestSupport::fixture();

    $result = app(CarrierQuoteValidationService::class)->validate([
        'carrier_id' => $fixture['carrier']->id,
        'price' => 0,
        'currency' => 'EUR',
        'delivery_date' => '2026-07-25',
    ], [
        'company_id' => $fixture['company']->id,
        'required_delivery_date' => '2026-07-20',
    ]);

    expect($result['warnings'])->toContain('late_delivery')
        ->and($result['warnings'])->toContain('zero_price');
});
