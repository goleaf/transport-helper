<?php

use App\Services\Supply\Transport\CarrierQuoteScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\TransportTestSupport;

uses(RefreshDatabase::class);

it('scores quote with price date and reliability', function () {
    $fixture = TransportTestSupport::fixture();
    $quote = TransportTestSupport::quote($fixture);

    $score = app(CarrierQuoteScoringService::class)->score($quote, ['required_delivery_date' => '2026-07-20']);

    expect($score['score'])->toBeGreaterThan(0)
        ->and($score['subscores'])->toHaveKeys(['price', 'delivery_date', 'pickup_date', 'reliability']);
});

it('lower price but late delivery can score worse', function () {
    $fixture = TransportTestSupport::fixture();
    $cheapLate = TransportTestSupport::quote($fixture, [
        'carrier_id' => $fixture['lateCarrier']->id,
        'price' => 400,
        'delivery_date' => '2026-07-30',
        'reliability_score' => 70,
    ]);
    $onTime = TransportTestSupport::quote($fixture, [
        'price' => 500,
        'delivery_date' => '2026-07-20',
        'reliability_score' => 95,
    ]);
    $quotes = collect([$cheapLate, $onTime]);
    $service = app(CarrierQuoteScoringService::class);

    $cheapLateScore = $service->score($cheapLate, ['required_delivery_date' => '2026-07-20', 'competing_quotes' => $quotes]);
    $onTimeScore = $service->score($onTime, ['required_delivery_date' => '2026-07-20', 'competing_quotes' => $quotes]);

    expect($onTimeScore['score'])->toBeGreaterThan($cheapLateScore['score'])
        ->and($cheapLateScore['warnings'])->toContain('late_delivery');
});

it('score explanation contains subscores and penalties and clamps score', function () {
    $fixture = TransportTestSupport::fixture();
    $quote = TransportTestSupport::quote($fixture, [
        'price' => null,
        'delivery_date' => null,
    ]);

    $score = app(CarrierQuoteScoringService::class)->score($quote);

    expect($score['score'])->toBeGreaterThanOrEqual(0)
        ->and($score['score'])->toBeLessThanOrEqual(100)
        ->and($score['explanation']['subscores'])->toBeArray()
        ->and($score['penalties'])->not->toBeEmpty();
});
