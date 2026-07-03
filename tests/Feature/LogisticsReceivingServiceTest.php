<?php

use App\Enums\LogisticsStatus;
use App\Enums\SupplierOrderStatus;
use App\Models\AuditLog;
use App\Services\Supply\Logistics\LogisticsReceivingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Support\LogisticsTestSupport;

uses(RefreshDatabase::class);

it('records goods receipt successfully', function () {
    $fixture = LogisticsTestSupport::fixture();
    $originalConfirmed = (string) $fixture['supplierOrderItem']->confirmed_quantity;

    $result = app(LogisticsReceivingService::class)->recordReceipt(
        $fixture['supplierOrder'],
        LogisticsTestSupport::receiptPayload($fixture),
        $fixture['user'],
    );

    expect((float) $fixture['supplierOrderItem']->fresh()->received_quantity)->toBe(156.0)
        ->and((string) $fixture['supplierOrderItem']->fresh()->confirmed_quantity)->toBe($originalConfirmed)
        ->and((float) $fixture['inboundOrderItem']->fresh()->received_quantity)->toBe(156.0)
        ->and($result['record']->status)->toBe(LogisticsStatus::Completed)
        ->and($fixture['supplierOrder']->fresh()->status)->toBe(SupplierOrderStatus::Completed)
        ->and(AuditLog::query()->where('event_type', 'goods_receipt_recorded')->exists())->toBeTrue();
});

it('blocks receiving mismatch unless confirmed', function () {
    $fixture = LogisticsTestSupport::fixture();

    app(LogisticsReceivingService::class)->recordReceipt(
        $fixture['supplierOrder'],
        LogisticsTestSupport::receiptPayload($fixture, [
            'items' => [[
                'product_id' => $fixture['product']->id,
                'received_quantity' => 150,
                'damaged_quantity' => 0,
            ]],
        ]),
        $fixture['user'],
    );
})->throws(ValidationException::class);

it('records confirmed mismatch as needs review', function () {
    $fixture = LogisticsTestSupport::fixture();

    $result = app(LogisticsReceivingService::class)->recordReceipt(
        $fixture['supplierOrder'],
        LogisticsTestSupport::receiptPayload($fixture, [
            'confirm_mismatches' => true,
            'items' => [[
                'product_id' => $fixture['product']->id,
                'received_quantity' => 150,
                'damaged_quantity' => 1,
                'notes' => 'One carton short.',
            ]],
        ]),
        $fixture['user'],
    );

    expect($result['record']->status)->toBe(LogisticsStatus::NeedsReview)
        ->and($fixture['supplierOrder']->fresh()->status)->toBe(SupplierOrderStatus::NeedsReview)
        ->and(collect($result['discrepancies']['discrepancies'])->pluck('type'))->toContain('received_less_than_expected');
});

it('cancelled order cannot receive', function () {
    $fixture = LogisticsTestSupport::fixture([
        'supplier_order' => ['status' => SupplierOrderStatus::Cancelled],
    ]);

    app(LogisticsReceivingService::class)->recordReceipt(
        $fixture['supplierOrder'],
        LogisticsTestSupport::receiptPayload($fixture),
        $fixture['user'],
    );
})->throws(ValidationException::class);
