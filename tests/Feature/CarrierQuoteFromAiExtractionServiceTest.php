<?php

use App\Services\Supply\Transport\CarrierQuoteFromAiExtractionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Support\TransportTestSupport;

uses(RefreshDatabase::class);

it('applies from accepted transport quote extraction', function () {
    $fixture = TransportTestSupport::fixture();
    $extraction = TransportTestSupport::acceptedAiExtraction($fixture);

    $result = app(CarrierQuoteFromAiExtractionService::class)->apply($extraction, $fixture['user']);

    expect($result['quote']->created_from_ai_extraction_id)->toBe($extraction->id)
        ->and($result['quote']->status->value)->not->toBe('selected');
});

it('rejects unaccepted extraction', function () {
    $fixture = TransportTestSupport::fixture();
    $extraction = TransportTestSupport::acceptedAiExtraction($fixture);
    $extraction->forceFill(['accepted_at' => null])->save();

    app(CarrierQuoteFromAiExtractionService::class)->apply($extraction, $fixture['user']);
})->throws(ValidationException::class);

it('rejects extraction without carrier quote data', function () {
    $fixture = TransportTestSupport::fixture();
    $extraction = TransportTestSupport::acceptedAiExtraction($fixture);
    $extraction->forceFill(['output_json' => ['email_type' => 'supplier_confirmation']])->save();

    app(CarrierQuoteFromAiExtractionService::class)->apply($extraction, $fixture['user']);
})->throws(ValidationException::class);
