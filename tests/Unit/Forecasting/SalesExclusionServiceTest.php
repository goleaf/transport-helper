<?php

use App\Models\AuditLog;
use App\Models\SalesExclusionRule;
use App\Models\SalesHistory;
use App\Services\Supply\Forecasting\SalesExclusionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ForecastingTestSupport;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('create exclusion rule requires reason', function (): void {
    $fixture = ForecastingTestSupport::fixture();

    app(SalesExclusionService::class)->createRule([
        'company_id' => $fixture['company']->id,
        'product_id' => $fixture['product']->id,
        'rule_type' => 'manual_exclusion',
        'date_from' => '2026-06-01',
        'date_to' => '2026-06-30',
        'applies_to' => 'all_calculation_periods',
        'reason' => '',
    ], $fixture['user']);
})->throws(InvalidArgumentException::class);

it('matching rules by product category and supplier', function (): void {
    $fixture = ForecastingTestSupport::fixture();
    $productRule = SalesExclusionRule::factory()->for($fixture['company'])->for($fixture['product'])->create([
        'date_from' => '2026-06-01',
        'date_to' => '2026-06-30',
    ]);
    $categoryRule = SalesExclusionRule::factory()->for($fixture['company'])->create([
        'category' => $fixture['product']->category,
        'date_from' => '2026-06-01',
        'date_to' => '2026-06-30',
    ]);
    $supplierRule = SalesExclusionRule::factory()->for($fixture['company'])->for($fixture['supplier'])->create([
        'date_from' => '2026-06-01',
        'date_to' => '2026-06-30',
    ]);

    $rules = app(SalesExclusionService::class)->matchingRules($fixture['company'], $fixture['product'], '2026-06-01', '2026-06-30', [
        'supplier_id' => $fixture['supplier']->id,
    ]);

    expect(collect($rules)->pluck('id')->all())->toContain($productRule->id, $categoryRule->id, $supplierRule->id);
});

it('rule does not delete sales history and audit is written', function (): void {
    $fixture = ForecastingTestSupport::fixture();
    SalesHistory::factory()->for($fixture['company'])->for($fixture['product'])->create();

    app(SalesExclusionService::class)->createRule([
        'company_id' => $fixture['company']->id,
        'product_id' => $fixture['product']->id,
        'rule_type' => 'manual_exclusion',
        'date_from' => '2026-06-01',
        'date_to' => '2026-06-30',
        'applies_to' => 'all_calculation_periods',
        'reason' => 'Manual repeatability review.',
    ], $fixture['user']);

    expect(SalesHistory::query()->count())->toBe(1)
        ->and(AuditLog::query()->where('event_type', 'sales_exclusion_rule_created')->exists())->toBeTrue();
});
