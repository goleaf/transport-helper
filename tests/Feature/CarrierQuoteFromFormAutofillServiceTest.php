<?php

use App\Services\Supply\Transport\CarrierQuoteFromFormAutofillService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Support\TransportTestSupport;

uses(RefreshDatabase::class);

it('applies from validated carrier quote form run without selecting carrier', function () {
    $fixture = TransportTestSupport::fixture();
    $run = TransportTestSupport::carrierQuoteFormRun($fixture);

    $result = app(CarrierQuoteFromFormAutofillService::class)->apply($run, $fixture['user']);

    expect($result['quote']->created_from_form_autofill_run_id)->toBe($run->id)
        ->and($result['quote']->status->value)->not->toBe('selected')
        ->and($run->refresh()->status->value)->toBe('applied');
});

it('rejects unvalidated form run', function () {
    $fixture = TransportTestSupport::fixture();
    $run = TransportTestSupport::carrierQuoteFormRun($fixture, 'needs_review');

    app(CarrierQuoteFromFormAutofillService::class)->apply($run, $fixture['user']);
})->throws(ValidationException::class);

it('rejects incompatible context', function () {
    $fixture = TransportTestSupport::fixture();
    $run = TransportTestSupport::carrierQuoteFormRun($fixture, 'validated', 'supplier_confirmation');

    app(CarrierQuoteFromFormAutofillService::class)->apply($run, $fixture['user']);
})->throws(ValidationException::class);
