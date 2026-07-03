<?php

use App\Services\Forms\AiEmailFormExtractionValidationService;
use App\Services\Forms\FormAutofillContextBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FormAutofillTestSupport;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('validates field output and detects review triggers', function () {
    $fixture = FormAutofillTestSupport::fixture();
    $context = app(FormAutofillContextBuilder::class)->build($fixture['email'], $fixture['template']);
    $service = app(AiEmailFormExtractionValidationService::class);

    $valid = $service->validate($fixture['template'], FormAutofillTestSupport::aiOutput(), $context);
    $missing = FormAutofillTestSupport::aiOutput();
    unset($missing['fields']['sku']);
    $lowConfidence = FormAutofillTestSupport::aiOutput(['overall_confidence' => 0.2]);
    $unknownSku = FormAutofillTestSupport::aiOutput(['fields' => ['sku' => ['value' => 'UNKNOWN', 'confidence' => 0.99]]]);
    $quantityMismatch = FormAutofillTestSupport::aiOutput(['fields' => ['confirmed_quantity' => ['value' => '1', 'confidence' => 0.99]]]);

    expect($valid['field_results']['sku']['requires_review'])->toBeFalse()
        ->and($missing['fields'])->not->toHaveKey('sku')
        ->and($service->validate($fixture['template'], $missing, $context)['field_results']['sku']['review_reason'])->toContain('required_field_missing')
        ->and($service->validate($fixture['template'], $lowConfidence, $context)['warnings'])->toContain('overall_low_confidence')
        ->and($service->validate($fixture['template'], $unknownSku, $context)['field_results']['sku']['review_reason'])->toContain('unknown_sku')
        ->and($service->validate($fixture['template'], $quantityMismatch, $context)['field_results']['confirmed_quantity']['review_reason'])->toContain('quantity_mismatch')
        ->and($service->validate($fixture['template'], ['fields' => []], $context)['status'])->toBe('invalid');
});
