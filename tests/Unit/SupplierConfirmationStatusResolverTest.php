<?php

use App\Enums\LogisticsStatus;
use App\Enums\SupplierConfirmationStatus;
use App\Enums\SupplierOrderStatus;
use App\Services\Supply\Confirmations\SupplierConfirmationStatusResolver;
use Tests\TestCase;

uses(TestCase::class);

it('resolves confirmed when no discrepancies', function () {
    $result = app(SupplierConfirmationStatusResolver::class)->resolve([
        'discrepancies' => [],
        'blocking' => false,
    ], [['matched' => true]], ['ready_date' => '2026-07-10']);

    expect($result['supplier_confirmation_status'])->toBe(SupplierConfirmationStatus::Confirmed)
        ->and($result['supplier_order_status'])->toBe(SupplierOrderStatus::Confirmed)
        ->and($result['logistics_status'])->toBe(LogisticsStatus::Confirmed);
});

it('unknown sku resolves needs review', function () {
    $result = app(SupplierConfirmationStatusResolver::class)->resolve([
        'discrepancies' => [['type' => 'unknown_sku', 'severity' => 'blocking']],
        'blocking' => true,
    ], [], ['ready_date' => '2026-07-10']);

    expect($result['supplier_confirmation_status'])->toBe(SupplierConfirmationStatus::NeedsReview)
        ->and($result['supplier_order_status'])->toBe(SupplierOrderStatus::NeedsReview)
        ->and($result['logistics_status'])->toBe(LogisticsStatus::NeedsReview);
});

it('quantity mismatch resolves quantity mismatch and partially confirmed order', function () {
    $result = app(SupplierConfirmationStatusResolver::class)->resolve([
        'discrepancies' => [['type' => 'quantity_lower_than_ordered', 'severity' => 'warning']],
        'blocking' => false,
    ], [['matched' => true]], ['ready_date' => '2026-07-10']);

    expect($result['supplier_confirmation_status'])->toBe(SupplierConfirmationStatus::QuantityMismatch)
        ->and($result['supplier_order_status'])->toBe(SupplierOrderStatus::PartiallyConfirmed);
});

it('date delay resolves delayed order', function () {
    $result = app(SupplierConfirmationStatusResolver::class)->resolve([
        'discrepancies' => [['type' => 'delayed_ready_date', 'severity' => 'warning']],
        'blocking' => false,
    ], [['matched' => true]], ['ready_date' => '2026-07-12']);

    expect($result['supplier_confirmation_status'])->toBe(SupplierConfirmationStatus::DateMismatch)
        ->and($result['supplier_order_status'])->toBe(SupplierOrderStatus::Delayed)
        ->and($result['logistics_status'])->toBe(LogisticsStatus::Delayed);
});

it('missing ready date resolves waiting for ready date logistics', function () {
    $result = app(SupplierConfirmationStatusResolver::class)->resolve([
        'discrepancies' => [],
        'blocking' => false,
    ], [['matched' => true]], ['ready_date' => null]);

    expect($result['logistics_status'])->toBe(LogisticsStatus::WaitingForReadyDate);
});

it('blocking discrepancy has priority over quantity mismatch', function () {
    $result = app(SupplierConfirmationStatusResolver::class)->resolve([
        'discrepancies' => [
            ['type' => 'quantity_lower_than_ordered', 'severity' => 'warning'],
            ['type' => 'unknown_sku', 'severity' => 'blocking'],
        ],
        'blocking' => true,
    ], [], ['ready_date' => '2026-07-10']);

    expect($result['supplier_confirmation_status'])->toBe(SupplierConfirmationStatus::NeedsReview)
        ->and($result['supplier_order_status'])->toBe(SupplierOrderStatus::NeedsReview);
});
