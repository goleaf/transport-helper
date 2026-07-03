<?php

use App\Enums\LogisticsStatus;
use App\Models\AuditLog;
use App\Models\LogisticsRecord;
use App\Services\Supply\Logistics\LogisticsRecordService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Support\LogisticsTestSupport;

uses(RefreshDatabase::class);

it('creates or updates a logistics record for a supplier order', function () {
    $fixture = LogisticsTestSupport::fixture();
    LogisticsRecord::query()->whereKey($fixture['logisticsRecord']->id)->delete();

    $result = app(LogisticsRecordService::class)->createOrUpdateForSupplierOrder($fixture['supplierOrder'], [
        'ready_date' => '2026-07-11',
    ], $fixture['user']);

    expect($result['record'])->toBeInstanceOf(LogisticsRecord::class)
        ->and($result['record']->ready_date?->toDateString())->toBe('2026-07-11')
        ->and(AuditLog::query()->where('event_type', 'logistics_record_created')->exists())->toBeTrue();
});

it('manual update changes dates status and notes with a reason', function () {
    $fixture = LogisticsTestSupport::fixture();

    $result = app(LogisticsRecordService::class)->manualUpdate($fixture['logisticsRecord'], [
        'status' => LogisticsStatus::Delayed->value,
        'delivery_date' => '2026-07-25',
        'notes' => 'Supplier delayed loading.',
        'reason' => 'Carrier update received.',
    ], $fixture['user']);

    expect($result['record']->status)->toBe(LogisticsStatus::Delayed)
        ->and($result['record']->delivery_date?->toDateString())->toBe('2026-07-25')
        ->and(AuditLog::query()->where('event_type', 'logistics_manual_update')->exists())->toBeTrue();
});

it('manual update requires reason when status changes', function () {
    $fixture = LogisticsTestSupport::fixture();

    app(LogisticsRecordService::class)->manualUpdate($fixture['logisticsRecord'], [
        'status' => LogisticsStatus::Delayed->value,
    ], $fixture['user']);
})->throws(ValidationException::class);

it('manual update rejects delivery before pickup without override', function () {
    $fixture = LogisticsTestSupport::fixture();

    app(LogisticsRecordService::class)->manualUpdate($fixture['logisticsRecord'], [
        'pickup_date' => '2026-07-20',
        'delivery_date' => '2026-07-19',
        'status' => LogisticsStatus::PickupScheduled->value,
        'reason' => 'Manual correction.',
    ], $fixture['user']);
})->throws(ValidationException::class);

it('updates status with audit event', function () {
    $fixture = LogisticsTestSupport::fixture();

    app(LogisticsRecordService::class)->updateStatus($fixture['logisticsRecord'], LogisticsStatus::Delayed->value, 'Late delivery', $fixture['user']);

    expect($fixture['logisticsRecord']->fresh()->status)->toBe(LogisticsStatus::Delayed)
        ->and(AuditLog::query()->where('event_type', 'logistics_status_changed')->exists())->toBeTrue();
});
