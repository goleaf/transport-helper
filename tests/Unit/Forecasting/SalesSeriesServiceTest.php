<?php

use App\Models\SalesExclusionRule;
use App\Models\SalesHistory;
use App\Services\Supply\Forecasting\SalesSeriesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ForecastingTestSupport;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('sales sum includes normal sales', function (): void {
    $fixture = ForecastingTestSupport::fixture();
    SalesHistory::factory()->for($fixture['company'])->for($fixture['product'])->create([
        'sales_date' => '2026-06-10',
        'quantity' => 25,
    ]);

    $sum = app(SalesSeriesService::class)->salesSum($fixture['company'], $fixture['product'], '2026-06-01', '2026-06-30');

    expect($sum['sum'])->toBe(25.0)
        ->and($sum['included_rows_count'])->toBe(1);
});

it('sales sum excludes promotions when enabled', function (): void {
    $fixture = ForecastingTestSupport::fixture();
    SalesHistory::factory()->for($fixture['company'])->for($fixture['product'])->create([
        'sales_date' => '2026-06-10',
        'quantity' => 25,
        'is_promotion' => true,
    ]);

    $sum = app(SalesSeriesService::class)->salesSum($fixture['company'], $fixture['product'], '2026-06-01', '2026-06-30', [
        'exclude_promotions' => true,
    ]);

    expect($sum['sum'])->toBe(0.0)
        ->and($sum['excluded_reasons'])->toHaveKey('promotion');
});

it('sales sum excludes anomalies when enabled', function (): void {
    $fixture = ForecastingTestSupport::fixture();
    SalesHistory::factory()->for($fixture['company'])->for($fixture['product'])->create([
        'sales_date' => '2026-06-10',
        'quantity' => 25,
        'is_anomaly' => true,
    ]);

    $sum = app(SalesSeriesService::class)->salesSum($fixture['company'], $fixture['product'], '2026-06-01', '2026-06-30', [
        'exclude_anomalies' => true,
    ]);

    expect($sum['sum'])->toBe(0.0)
        ->and($sum['excluded_reasons'])->toHaveKey('anomaly');
});

it('sales sum applies manual exclusion rules', function (): void {
    $fixture = ForecastingTestSupport::fixture();
    SalesHistory::factory()->for($fixture['company'])->for($fixture['product'])->create([
        'sales_date' => '2026-06-10',
        'quantity' => 25,
    ]);
    $rule = SalesExclusionRule::factory()->for($fixture['company'])->for($fixture['product'])->create([
        'rule_type' => 'manual_exclusion',
        'date_from' => '2026-06-01',
        'date_to' => '2026-06-30',
        'reason' => 'One time non-repeatable project.',
    ]);

    $sum = app(SalesSeriesService::class)->salesSum($fixture['company'], $fixture['product'], '2026-06-01', '2026-06-30', [
        'exclusion_rule_ids' => [$rule->id],
    ]);

    expect($sum['sum'])->toBe(0.0)
        ->and($sum['excluded_reasons'])->toHaveKey('manual_exclusion');
});

it('daily series returns expected dates', function (): void {
    $fixture = ForecastingTestSupport::fixture();

    $series = app(SalesSeriesService::class)->dailySeries($fixture['company'], $fixture['product'], '2026-06-01', '2026-06-03');

    expect(collect($series)->pluck('date')->all())->toBe(['2026-06-01', '2026-06-02', '2026-06-03']);
});

it('outlier candidates are detected but not excluded by default', function (): void {
    $fixture = ForecastingTestSupport::fixture();
    foreach ([10, 11, 100] as $quantity) {
        SalesHistory::factory()->for($fixture['company'])->for($fixture['product'])->create([
            'sales_date' => '2026-06-10',
            'quantity' => $quantity,
        ]);
    }

    $sum = app(SalesSeriesService::class)->salesSum($fixture['company'], $fixture['product'], '2026-06-01', '2026-06-30', [
        'outlier_detection' => true,
        'outlier_multiplier' => 3.0,
    ]);

    expect($sum['sum'])->toBe(121.0)
        ->and($sum['warnings'])->toContain('outlier_candidates_detected_not_excluded');
});
