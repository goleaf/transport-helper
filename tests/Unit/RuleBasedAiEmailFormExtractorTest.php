<?php

use App\Services\AI\Forms\RuleBasedAiEmailFormExtractor;

it('extracts only defined fields with source excerpts and human review', function () {
    $extractor = new RuleBasedAiEmailFormExtractor;
    $output = $extractor->extract([
        'email' => [
            'subject' => 'Confirmation for PO-20260814-1',
            'body_text' => 'Confirmation no. CONF-1. SKU-123 confirmed 156 pcs. Ready 2026-08-14. EUR 500.',
        ],
        'template' => ['context_type' => 'supplier_confirmation'],
        'fields' => [
            ['field_key' => 'supplier_order_number', 'field_type' => 'text'],
            ['field_key' => 'sku', 'field_type' => 'sku'],
            ['field_key' => 'confirmed_quantity', 'field_type' => 'decimal'],
            ['field_key' => 'ready_date', 'field_type' => 'date'],
        ],
        'context' => ['known_carriers' => []],
    ]);

    expect($output['fields'])->toHaveKeys(['supplier_order_number', 'sku', 'confirmed_quantity', 'ready_date'])
        ->and($output['fields']['supplier_order_number']['value'])->toBe('PO-20260814-1')
        ->and($output['fields']['sku']['value'])->toBe('SKU-123')
        ->and($output['fields']['confirmed_quantity']['normalized_value'])->toBe(156.0)
        ->and($output['fields']['ready_date']['source_excerpt'])->not->toBeNull()
        ->and($output['requires_human_review'])->toBeTrue();
});
