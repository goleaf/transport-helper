<?php

use App\Enums\SupplierOrderStatus;
use App\Models\SupplierConfirmation;
use App\Services\Supply\Confirmations\SupplierConfirmationFromAiExtractionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Support\SupplierConfirmationTestSupport;

uses(RefreshDatabase::class);

it('applies from accepted ai extraction', function () {
    $fixture = SupplierConfirmationTestSupport::fixture();
    $extraction = SupplierConfirmationTestSupport::acceptedAiExtraction($fixture);

    $result = app(SupplierConfirmationFromAiExtractionService::class)->apply($extraction, $fixture['user']);

    expect($result['confirmation'])->toBeInstanceOf(SupplierConfirmation::class)
        ->and($result['confirmation']->created_from_ai_extraction_id)->toBe($extraction->getKey())
        ->and($fixture['supplierOrderItem']->fresh()->confirmed_quantity)->not->toBeNull();
});

it('rejects unaccepted extraction', function () {
    $fixture = SupplierConfirmationTestSupport::fixture();
    $extraction = SupplierConfirmationTestSupport::acceptedAiExtraction($fixture);
    $extraction->forceFill(['accepted_at' => null])->save();

    app(SupplierConfirmationFromAiExtractionService::class)->apply($extraction, $fixture['user']);
})->throws(ValidationException::class);

it('rejects rejected extraction', function () {
    $fixture = SupplierConfirmationTestSupport::fixture();
    $extraction = SupplierConfirmationTestSupport::acceptedAiExtraction($fixture);
    $extraction->forceFill(['rejected_at' => now()])->save();

    app(SupplierConfirmationFromAiExtractionService::class)->apply($extraction, $fixture['user']);
})->throws(ValidationException::class);

it('resolves order from output order number', function () {
    $fixture = SupplierConfirmationTestSupport::fixture();
    $fixture['email']->forceFill(['related_supplier_order_id' => null])->save();
    $extraction = SupplierConfirmationTestSupport::acceptedAiExtraction($fixture);

    $result = app(SupplierConfirmationFromAiExtractionService::class)->apply($extraction, $fixture['user']);

    expect($result['supplier_order']->order_number)->toBe('PO-CONF-1');
});

it('blocks when order is missing', function () {
    $fixture = SupplierConfirmationTestSupport::fixture();
    $fixture['email']->forceFill(['related_supplier_order_id' => null])->save();
    $extraction = SupplierConfirmationTestSupport::acceptedAiExtraction($fixture, [
        'supplier_order_number' => 'PO-NOT-FOUND',
    ]);

    app(SupplierConfirmationFromAiExtractionService::class)->apply($extraction, $fixture['user']);
})->throws(ValidationException::class);

it('applying ai extraction does not skip laravel validation', function () {
    $fixture = SupplierConfirmationTestSupport::fixture();
    $extraction = SupplierConfirmationTestSupport::acceptedAiExtraction($fixture, [
        'confirmed_items' => [
            ['sku' => 'UNKNOWN', 'confirmed_quantity' => 10],
        ],
    ]);

    $result = app(SupplierConfirmationFromAiExtractionService::class)->apply($extraction, $fixture['user']);

    expect($result['supplier_order']->status)->toBe(SupplierOrderStatus::NeedsReview)
        ->and(collect($result['discrepancies'])->pluck('type'))->toContain('unknown_sku');
});
