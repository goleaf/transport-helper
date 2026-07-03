<?php

use App\Models\ProcurementException;
use App\Services\Supply\Procurement\ProcurementComplianceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ProcurementTestSupport;

uses(RefreshDatabase::class);

it('checks compliance under budget and detects missing price approval requirement', function (): void {
    $fixture = ProcurementTestSupport::fixture(['order_unit_price' => null]);

    $result = app(ProcurementComplianceService::class)->check($fixture['proposal']);

    expect($result['estimated_value']['missing_price_count'])->toBe(1)
        ->and($result['approval_requirements']['requires_approval'])->toBeTrue()
        ->and($result['warnings'])->toContain('missing_price');
});

it('detects budget overrun and respects approved exception record', function (): void {
    $fixture = ProcurementTestSupport::fixture(['budget_line_amount' => 50, 'order_unit_price' => null]);
    ProcurementTestSupport::price($fixture['company'], $fixture['supplier'], $fixture['product'], 10);

    ProcurementException::factory()->for($fixture['company'])->create([
        'exceptable_type' => $fixture['proposal']::class,
        'exceptable_id' => $fixture['proposal']->id,
        'exception_type' => 'budget_overrun',
        'status' => 'approved',
        'reason' => 'Approved overrun.',
        'requested_by_user_id' => $fixture['user']->id,
        'approved_by_user_id' => $fixture['manager']->id,
        'approved_at' => now(),
    ]);

    $result = app(ProcurementComplianceService::class)->check($fixture['proposal'], [
        'price_map' => [$fixture['product']->id => 10],
    ]);

    expect($result['budget_check']['over_budget_amount'])->toBeGreaterThan(0)
        ->and($result['exceptions']['approved_types'])->toContain('budget_overrun');
});
