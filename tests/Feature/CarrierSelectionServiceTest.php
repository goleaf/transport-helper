<?php

use App\Enums\CarrierQuoteStatus;
use App\Enums\LogisticsStatus;
use App\Services\Supply\Transport\CarrierSelectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Support\TransportTestSupport;

uses(RefreshDatabase::class);

it('user selects received quote and updates logistics', function () {
    $fixture = TransportTestSupport::fixture();
    $quote = TransportTestSupport::quote($fixture);

    $result = app(CarrierSelectionService::class)->select($quote, $fixture['user'], ['confirmation' => true]);

    expect($result['quote']->status)->toBe(CarrierQuoteStatus::Selected)
        ->and($result['quote']->selected_by_user_id)->toBe($fixture['user']->id)
        ->and($result['logistics_record']->carrier_id)->toBe($fixture['carrier']->id)
        ->and((float) $result['logistics_record']->transport_price)->toBe(500.0)
        ->and($result['logistics_record']->status)->toBe(LogisticsStatus::PickupScheduled);
});

it('cannot select needs review without override', function () {
    $fixture = TransportTestSupport::fixture();
    $quote = TransportTestSupport::quote($fixture, ['status' => 'needs_review']);

    app(CarrierSelectionService::class)->select($quote, $fixture['user'], ['confirmation' => true]);
})->throws(ValidationException::class);

it('can select needs review with override and reason', function () {
    $fixture = TransportTestSupport::fixture();
    $quote = TransportTestSupport::quote($fixture, ['status' => 'needs_review']);

    $result = app(CarrierSelectionService::class)->select($quote, $fixture['user'], [
        'confirmation' => true,
        'override_needs_review' => true,
        'override_reason' => 'Reviewed missing field manually.',
    ]);

    expect($result['quote']->status)->toBe(CarrierQuoteStatus::Selected)
        ->and($result['logistics_record']->status)->toBe(LogisticsStatus::NeedsReview);
});

it('requires replace existing when selected quote already exists', function () {
    $fixture = TransportTestSupport::fixture();
    TransportTestSupport::quote($fixture, ['status' => 'selected', 'selected_by_user_id' => $fixture['user']->id, 'selected_at' => now()]);
    $newQuote = TransportTestSupport::quote($fixture);

    app(CarrierSelectionService::class)->select($newQuote, $fixture['user'], ['confirmation' => true]);
})->throws(ValidationException::class);

it('replace existing selected quote and can reject others', function () {
    $fixture = TransportTestSupport::fixture();
    $oldQuote = TransportTestSupport::quote($fixture, ['status' => 'selected', 'selected_by_user_id' => $fixture['user']->id, 'selected_at' => now()]);
    $otherQuote = TransportTestSupport::quote($fixture, ['carrier_id' => $fixture['lateCarrier']->id]);
    $newQuote = TransportTestSupport::quote($fixture);

    app(CarrierSelectionService::class)->select($newQuote, $fixture['user'], [
        'confirmation' => true,
        'replace_existing' => true,
        'reject_others' => true,
    ]);

    expect($newQuote->refresh()->status)->toBe(CarrierQuoteStatus::Selected)
        ->and($oldQuote->refresh()->status)->toBe(CarrierQuoteStatus::Received)
        ->and($otherQuote->refresh()->status)->toBe(CarrierQuoteStatus::Rejected);
});
