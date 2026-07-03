<?php

use App\Models\OrderProposal;
use App\Models\SupplierOrder;
use App\Services\Supply\Procurement\OrderValueEstimationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ProcurementTestSupport;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('estimates supplier order from item unit price without mutating records', function (): void {
    $fixture = ProcurementTestSupport::fixture(['ordered_quantity' => 12, 'order_unit_price' => 5]);

    $result = app(OrderValueEstimationService::class)->estimateSupplierOrder($fixture['order']);

    expect($result['total'])->toBe(60.0)
        ->and($result['confidence'])->toBe('high')
        ->and($result['lines'][0]['price_source'])->toBe('supplier_order_item')
        ->and(SupplierOrder::query()->find($fixture['order']->id)->status->value)->toBe('draft');
});

it('estimates proposal from active supplier product price', function (): void {
    $fixture = ProcurementTestSupport::fixture(['proposal_quantity' => 20, 'order_unit_price' => null]);
    ProcurementTestSupport::price($fixture['company'], $fixture['supplier'], $fixture['product'], 7.5);

    $result = app(OrderValueEstimationService::class)->estimateProposal($fixture['proposal']);

    expect($result['total'])->toBe(150.0)
        ->and($result['lines'][0]['price_source'])->toBe('supplier_product_price');
});

it('uses previous order price when no active price exists', function (): void {
    $fixture = ProcurementTestSupport::fixture(['proposal_quantity' => 10, 'order_unit_price' => 9]);

    $result = app(OrderValueEstimationService::class)->estimateProposal($fixture['proposal']);

    expect($result['total'])->toBe(90.0)
        ->and($result['confidence'])->toBe('medium')
        ->and($result['warnings'])->toContain('previous_price_used');
});

it('returns missing price warning and low confidence', function (): void {
    $fixture = ProcurementTestSupport::fixture(['proposal_quantity' => 10, 'order_unit_price' => null]);

    $result = app(OrderValueEstimationService::class)->estimateProposal($fixture['proposal']);

    expect($result['missing_price_count'])->toBe(1)
        ->and($result['confidence'])->toBe('low')
        ->and($result['warnings'])->toContain('missing_price');
});

it('requires manual currency rate for multiple currencies', function (): void {
    $fixture = ProcurementTestSupport::fixture(['proposal_quantity' => 10, 'order_unit_price' => null]);
    ProcurementTestSupport::price($fixture['company'], $fixture['supplier'], $fixture['product'], 10)
        ->forceFill(['currency' => 'USD'])
        ->save();

    $result = app(OrderValueEstimationService::class)->estimateProposal($fixture['proposal'], ['currency' => 'EUR']);

    expect($result['warnings'])->toContain('currency_conversion_missing')
        ->and($result['requires_human_review'])->toBeTrue()
        ->and(OrderProposal::query()->find($fixture['proposal']->id)->status->value)->toBe('draft');
});
