<?php

use App\Models\AuditLog;
use App\Services\Supply\Procurement\SupplierProductPriceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ProcurementTestSupport;

uses(RefreshDatabase::class);

it('creates and finds a valid supplier product price by latest date with overlap warning', function (): void {
    $fixture = ProcurementTestSupport::fixture();
    $service = app(SupplierProductPriceService::class);

    $first = $service->createPrice([
        'company_id' => $fixture['company']->id,
        'supplier_id' => $fixture['supplier']->id,
        'product_id' => $fixture['product']->id,
        'currency' => 'EUR',
        'unit_price' => 10,
        'valid_from' => '2026-01-01',
        'valid_to' => null,
        'source_type' => 'manual',
    ], $fixture['manager']);
    $second = $service->createPrice([
        'company_id' => $fixture['company']->id,
        'supplier_id' => $fixture['supplier']->id,
        'product_id' => $fixture['product']->id,
        'currency' => 'EUR',
        'unit_price' => 12,
        'valid_from' => '2026-06-01',
        'valid_to' => null,
        'source_type' => 'manual',
    ], $fixture['manager']);
    $found = $service->findPrice($fixture['company'], $fixture['supplier'], $fixture['product'], '2026-07-01');

    expect($first['price']->unit_price)->toBe('10.0000')
        ->and($second['warnings'])->toContain('overlapping_active_price_period')
        ->and($found['price']->is($second['price']))->toBeTrue()
        ->and(AuditLog::query()->where('event_type', 'supplier_product_price_created')->count())->toBe(2);
});
