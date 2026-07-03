<?php

use App\Services\Supply\Confirmations\SupplierConfirmationApplicationService;
use App\Services\Supply\Confirmations\SupplierConfirmationSourceNormalizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\SupplierConfirmationTestSupport;

uses(RefreshDatabase::class);

it('creates inbound order when missing', function () {
    $fixture = SupplierConfirmationTestSupport::fixture();
    $fixture['inboundOrder']->delete();

    $result = app(SupplierConfirmationApplicationService::class)->apply(
        $fixture['supplierOrder'],
        app(SupplierConfirmationSourceNormalizer::class)->fromManual(SupplierConfirmationTestSupport::manualData()),
        $fixture['user'],
    );

    expect($result['inbound_order'])->not->toBeNull()
        ->and($result['inbound_order']->supplier_order_id)->toBe($fixture['supplierOrder']->getKey());
});

it('updates inbound order items for matched confirmation items', function () {
    $fixture = SupplierConfirmationTestSupport::fixture();

    app(SupplierConfirmationApplicationService::class)->apply(
        $fixture['supplierOrder'],
        app(SupplierConfirmationSourceNormalizer::class)->fromManual(SupplierConfirmationTestSupport::manualData()),
        $fixture['user'],
    );

    expect((float) $fixture['inboundOrderItem']->fresh()->confirmed_quantity)->toBe(156.0)
        ->and($fixture['inboundOrder']->fresh()->confirmed_arrival_date?->toDateString())->toBe('2026-07-20');
});

it('does not create inbound item for unknown sku', function () {
    $fixture = SupplierConfirmationTestSupport::fixture();

    app(SupplierConfirmationApplicationService::class)->apply(
        $fixture['supplierOrder'],
        app(SupplierConfirmationSourceNormalizer::class)->fromManual(SupplierConfirmationTestSupport::manualData([
            'items' => [['sku' => 'UNKNOWN', 'confirmed_quantity' => 1]],
        ])),
        $fixture['user'],
    );

    expect($fixture['inboundOrder']->fresh()->items()->count())->toBe(1);
});
