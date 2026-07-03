<?php

use App\Models\ProcurementException;
use App\Services\Supply\Procurement\SupplierOrderRuleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ProcurementTestSupport;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('warns on supplier minimum order value', function (): void {
    $fixture = ProcurementTestSupport::fixture();

    $result = app(SupplierOrderRuleService::class)->checkSupplierRules(
        $fixture['supplier'],
        ['total' => 50],
        ['enforcement_mode' => 'advisory', 'supplier_rules' => ['minimum_order_value' => 100]],
    );

    expect($result['status'])->toBe('warning')
        ->and($result['warnings'])->toContain('supplier_minimum_not_met');
});

it('blocks supplier maximum in enforced mode and detects order frequency', function (): void {
    $fixture = ProcurementTestSupport::fixture();

    $result = app(SupplierOrderRuleService::class)->checkSupplierRules(
        $fixture['supplier'],
        ['total' => 200],
        ['enforcement_mode' => 'enforced', 'supplier_rules' => [
            'maximum_order_value_without_approval' => 100,
            'maximum_orders_per_period' => 1,
            'order_frequency_period_days' => 30,
        ]],
    );

    expect($result['status'])->toBe('blocked')
        ->and($result['blocking_reasons'])->toContain('supplier_maximum_exceeded', 'order_frequency_violation');
});

it('approved exception can be recorded for a supplier rule violation', function (): void {
    $fixture = ProcurementTestSupport::fixture();

    ProcurementException::factory()->for($fixture['company'])->create([
        'exceptable_type' => $fixture['proposal']::class,
        'exceptable_id' => $fixture['proposal']->id,
        'exception_type' => 'supplier_minimum_not_met',
        'status' => 'approved',
        'requested_by_user_id' => $fixture['user']->id,
        'approved_by_user_id' => $fixture['manager']->id,
        'approved_at' => now(),
        'reason' => 'Approved urgent small order.',
    ]);

    expect(ProcurementException::query()->approved()->exists())->toBeTrue();
});
