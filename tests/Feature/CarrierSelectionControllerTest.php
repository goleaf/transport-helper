<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\TransportTestSupport;

uses(RefreshDatabase::class);

it('select quote route selects carrier and reject route rejects quote', function () {
    $fixture = TransportTestSupport::fixture();
    $quote = TransportTestSupport::quote($fixture);
    $rejectQuote = TransportTestSupport::quote($fixture, ['carrier_id' => $fixture['lateCarrier']->id]);

    $selectResponse = $this->actingAs($fixture['user'])->post(route('supply.transport.quotes.select', $quote), [
        'confirmation' => '1',
        'confirm_selection' => '1',
    ]);

    expect($selectResponse->isRedirect())->toBeTrue()
        ->and($quote->refresh()->status->value)->toBe('selected');

    $rejectResponse = $this->actingAs($fixture['user'])->post(route('supply.transport.quotes.reject', $rejectQuote), [
        'rejection_reason' => 'Too late',
    ]);

    expect($rejectResponse->isRedirect())->toBeTrue()
        ->and($rejectQuote->refresh()->status->value)->toBe('rejected');
});

it('apply AI and form autofill quote routes create candidates', function () {
    $fixture = TransportTestSupport::fixture();
    $extraction = TransportTestSupport::acceptedAiExtraction($fixture);
    $run = TransportTestSupport::carrierQuoteFormRun($fixture);

    $this->actingAs($fixture['user'])->post(route('supply.ai-extractions.apply-carrier-quote', $extraction), [
        'confirm_apply' => true,
    ])->assertRedirect();

    $this->actingAs($fixture['user'])->post(route('supply.form-autofill-runs.apply-carrier-quote', $run), [
        'confirm_apply' => true,
    ])->assertRedirect();

    expect($fixture['supplierOrder']->carrierQuotes()->count())->toBe(2);
});
