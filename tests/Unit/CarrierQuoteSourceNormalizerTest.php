<?php

use App\Services\Supply\Transport\CarrierQuoteSourceNormalizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\TransportTestSupport;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('normalizes manual quote data', function () {
    $fixture = TransportTestSupport::fixture();

    $quote = app(CarrierQuoteSourceNormalizer::class)->fromManual([
        'supplier_order_id' => $fixture['supplierOrder']->id,
        'carrier_name' => 'Fast Road',
        'price' => '500,25 EUR',
        'currency' => 'eur',
        'pickup_date' => '2026-07-10',
        'delivery_date' => '2026-07-20',
    ]);

    expect($quote['source_type'])->toBe('manual')
        ->and($quote['price'])->toBe(500.25)
        ->and($quote['currency'])->toBe('EUR')
        ->and($quote['delivery_date'])->toBe('2026-07-20');
});

it('normalizes accepted AI extraction carrier quote data', function () {
    $fixture = TransportTestSupport::fixture();
    $extraction = TransportTestSupport::acceptedAiExtraction($fixture);

    $quote = app(CarrierQuoteSourceNormalizer::class)->fromAiExtraction($extraction);

    expect($quote['source_type'])->toBe('ai_email_extraction')
        ->and($quote['source_id'])->toBe($extraction->id)
        ->and($quote['supplier_order_id'])->toBe($fixture['supplierOrder']->id)
        ->and($quote['source_excerpt'])->toContain('500 EUR');
});

it('normalizes validated form autofill carrier quote data', function () {
    $fixture = TransportTestSupport::fixture();
    $run = TransportTestSupport::carrierQuoteFormRun($fixture);

    $quote = app(CarrierQuoteSourceNormalizer::class)->fromFormAutofillRun($run);

    expect($quote['source_type'])->toBe('form_autofill_run')
        ->and($quote['carrier_name'])->toBe('Fast Road')
        ->and($quote['price'])->toBe(500.0)
        ->and($quote['source_excerpt'])->toContain('excerpt');
});
