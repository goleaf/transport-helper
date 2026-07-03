<?php

use App\Models\AuditLog;
use App\Services\Supply\Procurement\BudgetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ProcurementTestSupport;

uses(RefreshDatabase::class);

it('creates updates budget creates line and finds active budget', function (): void {
    $fixture = ProcurementTestSupport::fixture();
    $service = app(BudgetService::class);

    $created = $service->createBudget([
        'company_id' => $fixture['company']->id,
        'name' => 'Q3 procurement',
        'period_type' => 'quarterly',
        'date_from' => '2026-07-01',
        'date_to' => '2026-09-30',
        'currency' => 'EUR',
        'total_amount' => 5000,
        'status' => 'active',
    ], $fixture['manager'])['budget'];
    $updated = $service->updateBudget($created, ['total_amount' => 6000], $fixture['manager'])['budget'];
    $line = $service->addLine($updated, ['supplier_id' => $fixture['supplier']->id, 'amount' => 7000], $fixture['manager']);
    $active = $service->activeBudgetForDate($fixture['company'], '2026-08-01', 'EUR');

    expect($updated->total_amount)->toBe('6000.0000')
        ->and($line['warnings'])->toContain('budget_lines_exceed_total_budget')
        ->and($active)->not->toBeNull()
        ->and(AuditLog::query()->where('event_type', 'procurement_budget_line_created')->exists())->toBeTrue();
});
