<?php

use App\Services\Supply\Calculation\OrderRoundingService;

it('rounds 150 to 156 by pack multiple', function () {
    $result = app(OrderRoundingService::class)->round(150, [
        'pack_multiple' => 12,
    ]);

    expect($result['status'])->toBe('ok')
        ->and($result['quantity'])->toBe(156.0);
});

it('returns zero for negative raw need', function () {
    $result = app(OrderRoundingService::class)->round(-10);

    expect($result['quantity'])->toBe(0.0)
        ->and($result['warnings'])->toContain('raw_need_below_zero');
});

it('applies moq when raw need is positive', function () {
    $result = app(OrderRoundingService::class)->round(10, [
        'moq' => 24,
    ]);

    expect($result['quantity'])->toBe(24.0);
});

it('does not apply moq when raw need is zero by default', function () {
    $result = app(OrderRoundingService::class)->round(0, [
        'moq' => 24,
    ]);

    expect($result['quantity'])->toBe(0.0);
});

it('applies moq when strategic minimum is enabled', function () {
    $result = app(OrderRoundingService::class)->round(0, [
        'moq' => 24,
        'strategic_minimum_order_enabled' => true,
    ]);

    expect($result['quantity'])->toBe(24.0);
});

it('does not change quantity for pallet show only', function () {
    $result = app(OrderRoundingService::class)->round(150, [
        'pack_multiple' => 12,
        'pallet_quantity' => 144,
        'pallet_strategy' => 'show_only',
    ]);

    expect($result['quantity'])->toBe(156.0)
        ->and($result['warnings'])->toContain('pallet_quantity_show_only');
});

it('changes quantity when enforcing full pallet', function () {
    $result = app(OrderRoundingService::class)->round(150, [
        'pack_multiple' => 12,
        'pallet_quantity' => 144,
        'pallet_strategy' => 'enforce_full_pallet',
    ]);

    expect($result['quantity'])->toBe(288.0);
});

it('does not change quantity for transport show only', function () {
    $result = app(OrderRoundingService::class)->round(80, [
        'min_transport_quantity' => 120,
        'transport_strategy' => 'show_only',
    ]);

    expect($result['quantity'])->toBe(80.0)
        ->and($result['warnings'])->toContain('min_transport_quantity_show_only');
});

it('changes quantity when enforcing minimum transport', function () {
    $result = app(OrderRoundingService::class)->round(80, [
        'min_transport_quantity' => 120,
        'transport_strategy' => 'enforce_min_transport',
    ]);

    expect($result['quantity'])->toBe(120.0);
});
