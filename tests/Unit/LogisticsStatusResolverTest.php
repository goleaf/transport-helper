<?php

use App\Enums\LogisticsStatus;
use App\Enums\SupplierOrderStatus;
use App\Services\Supply\Logistics\LogisticsStatusResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\LogisticsTestSupport;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('suggests completed when actual receipt exists and there is no mismatch', function () {
    $fixture = LogisticsTestSupport::fixture([
        'logistics_record' => [
            'actual_received_date' => '2026-07-21',
            'receiving_discrepancies_json' => [],
            'status' => LogisticsStatus::Arrived,
        ],
        'supplier_order_item' => ['received_quantity' => 156],
    ]);

    $result = app(LogisticsStatusResolver::class)->suggestStatus($fixture['logisticsRecord']->fresh());

    expect($result['suggested_status'])->toBe(LogisticsStatus::Completed->value)
        ->and($result['reasons'])->toContain('receipt_reconciled');
});

it('suggests delayed when delivery date passed without receipt', function () {
    $fixture = LogisticsTestSupport::fixture([
        'logistics_record' => [
            'delivery_date' => now()->subDay()->toDateString(),
            'actual_received_date' => null,
            'status' => LogisticsStatus::PickupScheduled,
        ],
    ]);

    $result = app(LogisticsStatusResolver::class)->suggestStatus($fixture['logisticsRecord']->fresh());

    expect($result['suggested_status'])->toBe(LogisticsStatus::Delayed->value)
        ->and($result['reasons'])->toContain('delivery_date_passed_without_receipt');
});

it('suggests ready for pickup when ready date has arrived without pickup', function () {
    $fixture = LogisticsTestSupport::fixture([
        'logistics_record' => [
            'ready_date' => now()->subDay()->toDateString(),
            'pickup_date' => null,
            'delivery_date' => now()->addDays(5)->toDateString(),
            'status' => LogisticsStatus::Confirmed,
        ],
    ]);

    $result = app(LogisticsStatusResolver::class)->suggestStatus($fixture['logisticsRecord']->fresh());

    expect($result['suggested_status'])->toBe(LogisticsStatus::ReadyForPickup->value);
});

it('suggests waiting for ready date when confirmation exists but ready date is missing', function () {
    $fixture = LogisticsTestSupport::fixture([
        'logistics_record' => [
            'ready_date' => null,
            'status' => LogisticsStatus::Confirmed,
        ],
    ]);

    $result = app(LogisticsStatusResolver::class)->suggestStatus($fixture['logisticsRecord']->fresh());

    expect($result['suggested_status'])->toBe(LogisticsStatus::WaitingForReadyDate->value);
});

it('suggests order sent for sent supplier orders without confirmation', function () {
    $fixture = LogisticsTestSupport::fixture([
        'supplier_order' => ['status' => SupplierOrderStatus::Sent],
        'logistics_record' => [
            'supplier_confirmation_id' => null,
            'confirmation_date' => null,
            'ready_date' => null,
            'status' => LogisticsStatus::Planned,
        ],
    ]);

    $result = app(LogisticsStatusResolver::class)->suggestStatus($fixture['logisticsRecord']->fresh());

    expect($result['suggested_status'])->toBe(LogisticsStatus::OrderSent->value);
});
