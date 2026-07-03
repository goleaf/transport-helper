<?php

use App\Services\Supply\Logistics\LogisticsReceivingDiscrepancyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\LogisticsTestSupport;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('detects no discrepancy when received matches confirmed quantity', function () {
    $fixture = LogisticsTestSupport::fixture();

    $result = app(LogisticsReceivingDiscrepancyService::class)->detect($fixture['supplierOrder'], [
        ['product_id' => $fixture['product']->id, 'received_quantity' => 156],
    ]);

    expect($result['has_discrepancies'])->toBeFalse();
});

it('detects received less than expected', function () {
    $fixture = LogisticsTestSupport::fixture();

    $result = app(LogisticsReceivingDiscrepancyService::class)->detect($fixture['supplierOrder'], [
        ['product_id' => $fixture['product']->id, 'received_quantity' => 150],
    ], ['complete_order' => true]);

    expect($result['has_discrepancies'])->toBeTrue()
        ->and($result['discrepancies'][0]['type'])->toBe('received_less_than_expected');
});

it('detects received more than expected', function () {
    $fixture = LogisticsTestSupport::fixture();

    $result = app(LogisticsReceivingDiscrepancyService::class)->detect($fixture['supplierOrder'], [
        ['product_id' => $fixture['product']->id, 'received_quantity' => 160],
    ]);

    expect($result['discrepancies'][0]['type'])->toBe('received_more_than_expected');
});

it('detects unexpected item', function () {
    $fixture = LogisticsTestSupport::fixture();

    $result = app(LogisticsReceivingDiscrepancyService::class)->detect($fixture['supplierOrder'], [
        ['sku' => 'UNKNOWN-SKU', 'received_quantity' => 5],
    ]);

    expect($result['blocking'])->toBeTrue()
        ->and($result['discrepancies'][0]['type'])->toBe('unexpected_item');
});

it('detects damaged quantity', function () {
    $fixture = LogisticsTestSupport::fixture();

    $result = app(LogisticsReceivingDiscrepancyService::class)->detect($fixture['supplierOrder'], [
        ['product_id' => $fixture['product']->id, 'received_quantity' => 156, 'damaged_quantity' => 2],
    ]);

    expect(collect($result['discrepancies'])->pluck('type'))->toContain('damaged_quantity');
});
