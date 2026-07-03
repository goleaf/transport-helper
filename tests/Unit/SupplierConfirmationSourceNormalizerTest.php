<?php

use App\Services\Supply\Confirmations\SupplierConfirmationSourceNormalizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\SupplierConfirmationTestSupport;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('normalizes manual data', function () {
    $normalized = app(SupplierConfirmationSourceNormalizer::class)->fromManual(SupplierConfirmationTestSupport::manualData());

    expect($normalized['source_type'])->toBe('manual')
        ->and($normalized['supplier_reference'])->toBe('CONF-9001')
        ->and($normalized['ready_date'])->toBe('2026-07-10')
        ->and($normalized['items'][0]['supplier_sku'])->toBe('SUP-AX-150')
        ->and($normalized['items'][0]['confirmed_quantity'])->toBe(156.0);
});

it('normalizes ai extraction', function () {
    $fixture = SupplierConfirmationTestSupport::fixture();
    $extraction = SupplierConfirmationTestSupport::acceptedAiExtraction($fixture);

    $normalized = app(SupplierConfirmationSourceNormalizer::class)->fromAiExtraction($extraction);

    expect($normalized['source_type'])->toBe('ai_email_extraction')
        ->and($normalized['source_id'])->toBe($extraction->getKey())
        ->and($normalized['email_message_id'])->toBe($fixture['email']->getKey())
        ->and($normalized['items'][0]['sku'])->toBe('AX-150')
        ->and($normalized['items'][0]['source_excerpt'])->toContain('confirmed');
});

it('normalizes form autofill run and preserves source excerpt', function () {
    $fixture = SupplierConfirmationTestSupport::fixture();
    $run = SupplierConfirmationTestSupport::validatedFormRun($fixture);

    $normalized = app(SupplierConfirmationSourceNormalizer::class)->fromFormAutofillRun($run);

    expect($normalized['source_type'])->toBe('form_autofill_run')
        ->and($normalized['source_id'])->toBe($run->getKey())
        ->and($normalized['supplier_order_number'])->toBe('PO-CONF-1')
        ->and($normalized['items'][0]['source_excerpt'])->toContain('sku source excerpt');
});

it('turns invalid dates into warnings', function () {
    $normalized = app(SupplierConfirmationSourceNormalizer::class)->fromManual(SupplierConfirmationTestSupport::manualData([
        'ready_date' => 'not-a-date',
    ]));

    expect($normalized['ready_date'])->toBeNull()
        ->and($normalized['warnings'])->toContain('invalid_date:ready_date');
});

it('supports multi item ai extraction', function () {
    $fixture = SupplierConfirmationTestSupport::fixture(withSecondItem: true);
    $extraction = SupplierConfirmationTestSupport::acceptedAiExtraction($fixture, [
        'confirmed_items' => [
            ['sku' => 'AX-150', 'confirmed_quantity' => 156],
            ['supplier_sku' => 'SUP-BRK-200', 'confirmed_quantity' => 24],
        ],
    ]);

    $normalized = app(SupplierConfirmationSourceNormalizer::class)->fromAiExtraction($extraction);

    expect($normalized['items'])->toHaveCount(2)
        ->and($normalized['items'][1]['supplier_sku'])->toBe('SUP-BRK-200');
});
