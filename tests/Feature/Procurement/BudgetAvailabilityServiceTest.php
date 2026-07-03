<?php

use App\Services\Supply\Procurement\BudgetAvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ProcurementTestSupport;

uses(RefreshDatabase::class);

it('checks budget availability and detects overrun by matching supplier line', function (): void {
    $fixture = ProcurementTestSupport::fixture(['budget_line_amount' => 500, 'order_unit_price' => 1]);

    $result = app(BudgetAvailabilityService::class)->check($fixture['company'], [
        'total' => 600,
        'currency' => 'EUR',
    ], [
        'supplier_id' => $fixture['supplier']->id,
        'product_ids' => [$fixture['product']->id],
        'date' => '2026-07-04',
    ]);

    expect($result['budget_id'])->toBe($fixture['budget']->id)
        ->and($result['status'])->toBe('blocked')
        ->and($result['warnings'])->toContain('budget_overrun');
});

it('returns missing budget warning', function (): void {
    $fixture = ProcurementTestSupport::fixture();
    $fixture['budget']->delete();

    $result = app(BudgetAvailabilityService::class)->check($fixture['company'], ['total' => 10, 'currency' => 'EUR'], ['date' => '2026-07-04']);

    expect($result['status'])->toBe('warning')
        ->and($result['warnings'])->toContain('missing_budget');
});
