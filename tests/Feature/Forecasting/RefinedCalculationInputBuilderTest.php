<?php

use App\Enums\TrendOverrideStatus;
use App\Models\ReplenishmentProfile;
use App\Models\SalesExclusionRule;
use App\Models\SalesHistory;
use App\Models\TrendOverride;
use App\Services\Supply\Forecasting\RefinedCalculationInputBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ForecastingTestSupport;

uses(RefreshDatabase::class);

it('builds input with excluded promotions and anomalies', function (): void {
    $fixture = ForecastingTestSupport::fixture();
    ForecastingTestSupport::seedCalculationSales($fixture['company'], $fixture['product']);
    SalesHistory::factory()->for($fixture['company'])->for($fixture['product'])->create([
        'sales_date' => '2026-06-10',
        'quantity' => 500,
        'is_promotion' => true,
    ]);
    SalesHistory::factory()->for($fixture['company'])->for($fixture['product'])->create([
        'sales_date' => '2025-06-10',
        'quantity' => 300,
        'is_anomaly' => true,
    ]);

    $built = app(RefinedCalculationInputBuilder::class)->build($fixture['company'], $fixture['supplier'], $fixture['product'], ForecastingTestSupport::parameters(), $fixture['user']);

    expect($built['input']['current_year_sales_for_trend'])->toBe(120.0)
        ->and($built['input']['last_year_sales_for_trend'])->toBe(100.0)
        ->and($built['explanation']['sales_exclusions']['current_year_sales_for_trend']['sum']['excluded_reasons'])->toHaveKey('promotion');
});

it('builds input with approved trend override and ignores unapproved override with warning', function (): void {
    $fixture = ForecastingTestSupport::fixture();
    ForecastingTestSupport::seedCalculationSales($fixture['company'], $fixture['product']);
    TrendOverride::factory()->for($fixture['company'])->for($fixture['product'])->create([
        'status' => TrendOverrideStatus::PendingApproval,
        'date_from' => '2026-06-01',
        'date_to' => '2026-07-15',
    ]);
    $approved = TrendOverride::factory()->for($fixture['company'])->for($fixture['product'])->create([
        'trend_value' => 1.5,
        'status' => TrendOverrideStatus::Approved,
        'approved_by_user_id' => $fixture['user']->id,
        'approved_at' => now(),
        'date_from' => '2026-06-01',
        'date_to' => '2026-07-15',
    ]);

    $built = app(RefinedCalculationInputBuilder::class)->build($fixture['company'], $fixture['supplier'], $fixture['product'], ForecastingTestSupport::parameters(), $fixture['user']);

    expect($built['input']['manual_trend_override_id'])->toBe($approved->id)
        ->and($built['warnings'])->toContain('unapproved_trend_override_exists_not_used');
});

it('builds input with seasonality factor profile safety and manual exclusion', function (): void {
    $fixture = ForecastingTestSupport::fixture();
    ForecastingTestSupport::seedCalculationSales($fixture['company'], $fixture['product']);
    ForecastingTestSupport::seedMonthlyHistory($fixture['company'], $fixture['product']);
    ReplenishmentProfile::factory()->for($fixture['company'])->for($fixture['product'])->create([
        'seasonality_enabled' => true,
        'seasonality_mode' => 'multiply_period_sales',
        'safety_days_override' => 22,
    ]);
    SalesExclusionRule::factory()->for($fixture['company'])->for($fixture['product'])->create([
        'date_from' => '2025-07-01',
        'date_to' => '2025-07-15',
        'applies_to' => 't0_t1',
        'reason' => 'Opening promotion.',
    ]);

    $built = app(RefinedCalculationInputBuilder::class)->build($fixture['company'], $fixture['supplier'], $fixture['product'], ForecastingTestSupport::parameters([
        'scenario_options' => [
            'use_seasonality' => true,
            'seasonality_mode' => 'multiply_period_sales',
            'use_manual_overrides' => true,
        ],
    ]), $fixture['user']);

    expect($built['seasonality']['enabled'])->toBeTrue()
        ->and($built['applied_rules']['rules']['safety_days_override'])->toBe(22)
        ->and($built['explanation'])->toHaveKeys(['sales_exclusions', 'seasonality', 'manual_trend_override']);
});
