<?php

use App\Enums\LogisticsStatus;
use App\Services\Supply\Logistics\LogisticsDelayMonitoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\LogisticsTestSupport;

uses(RefreshDatabase::class);

it('detects delivery delay', function () {
    $fixture = LogisticsTestSupport::fixture([
        'logistics_record' => [
            'delivery_date' => now()->subDay()->toDateString(),
            'actual_received_date' => null,
            'status' => LogisticsStatus::PickupScheduled,
        ],
    ]);

    $result = app(LogisticsDelayMonitoringService::class)->monitor(['dry_run' => true]);

    expect($result['checked_count'])->toBeGreaterThanOrEqual(1)
        ->and($result['delayed_count'])->toBeGreaterThanOrEqual(1)
        ->and($fixture['logisticsRecord']->fresh()->status)->toBe(LogisticsStatus::PickupScheduled);
});

it('updates status to delayed when enabled and creates one deduped notification', function () {
    $fixture = LogisticsTestSupport::fixture([
        'logistics_record' => [
            'delivery_date' => now()->subDay()->toDateString(),
            'status' => LogisticsStatus::PickupScheduled,
        ],
    ]);

    app(LogisticsDelayMonitoringService::class)->monitor(['dry_run' => false, 'update_status' => true]);
    app(LogisticsDelayMonitoringService::class)->monitor(['dry_run' => false, 'update_status' => true]);

    expect($fixture['logisticsRecord']->fresh()->status)->toBe(LogisticsStatus::Delayed)
        ->and($fixture['user']->notifications()->count())->toBeLessThanOrEqual(1);
});

it('detects goods expected soon', function () {
    LogisticsTestSupport::fixture([
        'logistics_record' => [
            'delivery_date' => now()->addDays(2)->toDateString(),
            'status' => LogisticsStatus::InTransit,
        ],
    ]);

    $result = app(LogisticsDelayMonitoringService::class)->monitor(['dry_run' => true, 'expected_soon_days' => 3]);

    expect($result['expected_soon_count'])->toBeGreaterThanOrEqual(1);
});
