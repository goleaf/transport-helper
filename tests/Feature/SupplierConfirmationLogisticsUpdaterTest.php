<?php

use App\Enums\LogisticsStatus;
use App\Services\Supply\Confirmations\SupplierConfirmationApplicationService;
use App\Services\Supply\Confirmations\SupplierConfirmationSourceNormalizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\SupplierConfirmationTestSupport;

uses(RefreshDatabase::class);

it('creates logistics record when missing', function () {
    $fixture = SupplierConfirmationTestSupport::fixture();
    $fixture['logisticsRecord']->delete();

    $result = app(SupplierConfirmationApplicationService::class)->apply(
        $fixture['supplierOrder'],
        app(SupplierConfirmationSourceNormalizer::class)->fromManual(SupplierConfirmationTestSupport::manualData()),
        $fixture['user'],
    );

    expect($result['logistics_record'])->not->toBeNull()
        ->and($result['logistics_record']->supplier_confirmation_id)->toBe($result['confirmation']->getKey());
});

it('updates existing logistics record', function () {
    $fixture = SupplierConfirmationTestSupport::fixture();

    app(SupplierConfirmationApplicationService::class)->apply(
        $fixture['supplierOrder'],
        app(SupplierConfirmationSourceNormalizer::class)->fromManual(SupplierConfirmationTestSupport::manualData(['ready_date' => '2026-07-12'])),
        $fixture['user'],
    );

    expect($fixture['logisticsRecord']->fresh()->ready_date?->toDateString())->toBe('2026-07-12');
});

it('sets waiting for ready date when ready date missing', function () {
    $fixture = SupplierConfirmationTestSupport::fixture();

    app(SupplierConfirmationApplicationService::class)->apply(
        $fixture['supplierOrder'],
        app(SupplierConfirmationSourceNormalizer::class)->fromManual(SupplierConfirmationTestSupport::manualData(['ready_date' => null])),
        $fixture['user'],
    );

    expect($fixture['logisticsRecord']->fresh()->status)->toBe(LogisticsStatus::WaitingForReadyDate);
});
