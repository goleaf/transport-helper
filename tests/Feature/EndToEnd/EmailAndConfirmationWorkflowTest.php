<?php

use App\Models\SupplierConfirmation;
use App\Services\Supply\Confirmations\SupplierConfirmationFromAiExtractionService;
use App\Services\Supply\Confirmations\SupplierConfirmationFromFormAutofillService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Support\SupplierConfirmationTestSupport;

uses(RefreshDatabase::class);

it('ai extraction acceptance does not apply supplier confirmation by itself', function (): void {
    $fixture = SupplierConfirmationTestSupport::fixture();

    SupplierConfirmationTestSupport::acceptedAiExtraction($fixture);

    expect(SupplierConfirmation::query()->count())->toBe(0)
        ->and($fixture['supplierOrderItem']->fresh()->confirmed_quantity)->toBeNull();
});

it('accepted ai extraction can be applied through the application service', function (): void {
    $fixture = SupplierConfirmationTestSupport::fixture();
    $extraction = SupplierConfirmationTestSupport::acceptedAiExtraction($fixture);

    $result = app(SupplierConfirmationFromAiExtractionService::class)->apply($extraction, $fixture['user']);

    expect($result['confirmation'])->toBeInstanceOf(SupplierConfirmation::class)
        ->and((float) $fixture['supplierOrderItem']->fresh()->confirmed_quantity)->toBe(156.0);
});

it('unaccepted ai extraction cannot be applied', function (): void {
    $fixture = SupplierConfirmationTestSupport::fixture();
    $extraction = SupplierConfirmationTestSupport::acceptedAiExtraction($fixture);
    $extraction->forceFill(['accepted_at' => null])->save();

    app(SupplierConfirmationFromAiExtractionService::class)->apply($extraction, $fixture['user']);
})->throws(ValidationException::class);

it('validated form autofill can be applied as supplier confirmation', function (): void {
    $fixture = SupplierConfirmationTestSupport::fixture();
    $run = SupplierConfirmationTestSupport::validatedFormRun($fixture);

    $result = app(SupplierConfirmationFromFormAutofillService::class)->apply($run, $fixture['user']);

    expect($result['confirmation'])->toBeInstanceOf(SupplierConfirmation::class)
        ->and((float) $fixture['supplierOrderItem']->fresh()->confirmed_quantity)->toBe(156.0);
});

it('unvalidated form autofill cannot be applied as supplier confirmation', function (): void {
    $fixture = SupplierConfirmationTestSupport::fixture();
    $run = SupplierConfirmationTestSupport::validatedFormRun($fixture);
    $run->forceFill(['status' => 'needs_review'])->save();

    app(SupplierConfirmationFromFormAutofillService::class)->apply($run->fresh(), $fixture['user']);
})->throws(ValidationException::class);

it('quantity mismatch remains visible on applied confirmation', function (): void {
    $fixture = SupplierConfirmationTestSupport::fixture();
    $extraction = SupplierConfirmationTestSupport::acceptedAiExtraction($fixture, [
        'confirmed_items' => [[
            'sku' => 'AX-150',
            'confirmed_quantity' => 120,
            'source_excerpt' => 'AX-150 confirmed 120 pcs',
        ]],
    ]);

    $result = app(SupplierConfirmationFromAiExtractionService::class)->apply($extraction, $fixture['user']);

    expect($result['confirmation']->discrepancies_json)->not->toBeEmpty()
        ->and((float) $fixture['supplierOrderItem']->fresh()->confirmed_quantity)->toBe(120.0);
});
