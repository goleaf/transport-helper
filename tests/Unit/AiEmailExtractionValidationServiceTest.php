<?php

use App\Services\AI\Email\AiEmailExtractionValidationService;

it('valid supplier confirmation output remains a review candidate by default', function () {
    $validation = app(AiEmailExtractionValidationService::class)->validate(stage6AiOutput(), stage6AiContext());

    expect($validation['valid_shape'])->toBeTrue()
        ->and($validation['status'])->toBe('needs_review');
});

it('low confidence requires review', function () {
    $validation = app(AiEmailExtractionValidationService::class)->validate(stage6AiOutput(['confidence' => 0.5]), stage6AiContext());

    expect($validation['requires_human_review'])->toBeTrue()
        ->and($validation['warnings'])->toContain('low_confidence');
});

it('unclear email type requires review', function () {
    $validation = app(AiEmailExtractionValidationService::class)->validate(stage6AiOutput(['email_type' => 'unclear']), stage6AiContext());

    expect($validation['warnings'])->toContain('unclear_email_type');
});

it('unknown sku requires review', function () {
    $validation = app(AiEmailExtractionValidationService::class)->validate(stage6AiOutput([
        'confirmed_items' => [
            ['sku' => 'UNKNOWN', 'confirmed_quantity' => 156],
        ],
    ]), stage6AiContext());

    expect($validation['warnings'])->toContain('unknown_sku');
});

it('quantity mismatch requires review', function () {
    $validation = app(AiEmailExtractionValidationService::class)->validate(stage6AiOutput([
        'confirmed_items' => [
            ['sku' => 'SKU-1001', 'confirmed_quantity' => 120],
        ],
    ]), stage6AiContext());

    expect($validation['warnings'])->toContain('quantity_mismatch')
        ->and($validation['discrepancies'][0]['type'])->toBe('quantity_mismatch');
});

it('invalid date requires review', function () {
    $validation = app(AiEmailExtractionValidationService::class)->validate(stage6AiOutput([
        'dates' => ['ready_date' => 'not-a-date'],
    ]), stage6AiContext());

    expect($validation['warnings'])->toContain('invalid_date');
});

it('supplier confirmation without order requires review', function () {
    $context = stage6AiContext();
    $context['supplier_order'] = null;

    $validation = app(AiEmailExtractionValidationService::class)->validate(stage6AiOutput(), $context);

    expect($validation['warnings'])->toContain('unknown_supplier_order');
});

it('transport quote missing price requires review', function () {
    $validation = app(AiEmailExtractionValidationService::class)->validate(stage6AiOutput([
        'email_type' => 'transport_quote',
        'carrier_quote' => ['delivery_date' => null],
        'confirmed_items' => [],
    ]), stage6AiContext());

    expect($validation['warnings'])->toContain('transport_quote_missing_price_or_date');
});

it('invalid shape is invalid', function () {
    $validation = app(AiEmailExtractionValidationService::class)->validate(['confidence' => 0.9], stage6AiContext());

    expect($validation['status'])->toBe('invalid')
        ->and($validation['errors'])->toContain('missing_email_type');
});

function stage6AiOutput(array $overrides = []): array
{
    return array_replace_recursive([
        'email_type' => 'supplier_confirmation',
        'supplier_order_number' => 'PO-20260701-1',
        'supplier_reference' => 'CONF-123',
        'confirmed_items' => [
            ['sku' => 'SKU-1001', 'confirmed_quantity' => 156, 'unit' => 'pcs'],
        ],
        'dates' => [
            'confirmation_date' => '2026-07-02',
            'ready_date' => '2026-07-15',
        ],
        'carrier_quote' => [],
        'discrepancies' => [],
        'questions_to_supplier' => [],
        'confidence' => 0.91,
        'requires_human_review' => false,
        'human_review_reason' => null,
    ], $overrides);
}

function stage6AiContext(array $overrides = []): array
{
    return array_replace_recursive([
        'supplier' => ['id' => 1, 'name' => 'Acme'],
        'supplier_order' => ['id' => 1, 'order_number' => 'PO-20260701-1'],
        'expected_items' => [
            [
                'sku' => 'SKU-1001',
                'manufacturer_sku' => 'M-1001',
                'supplier_sku' => 'SUP-1001',
                'ordered_quantity' => 156,
            ],
        ],
    ], $overrides);
}
