<?php

use App\Models\Product;
use App\Models\SalesHistory;
use App\Services\Supply\Forecasting\SeasonalityFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ForecastingTestSupport;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('calculates factor from monthly history', function (): void {
    $fixture = ForecastingTestSupport::fixture();
    ForecastingTestSupport::seedMonthlyHistory($fixture['company'], $fixture['product']);

    $result = app(SeasonalityFactorService::class)->calculateFactor($fixture['company'], $fixture['product'], '2026-07-01', '2026-07-31');

    expect($result['factor'])->toBeGreaterThan(1.0)
        ->and($result['explanation']['calculation'])->toContain('/');
});

it('insufficient history returns factor one with warning', function (): void {
    $fixture = ForecastingTestSupport::fixture();

    $result = app(SeasonalityFactorService::class)->calculateFactor($fixture['company'], $fixture['product'], '2026-07-01', '2026-07-31');

    expect($result['factor'])->toBe(1.0)
        ->and($result['warnings'])->toContain('insufficient_history');
});

it('factor is clamped to min and max', function (): void {
    $fixture = ForecastingTestSupport::fixture();
    for ($month = 1; $month <= 12; $month++) {
        SalesHistory::factory()->for($fixture['company'])->for($fixture['product'])->create([
            'sales_date' => sprintf('2025-%02d-10', $month),
            'quantity' => $month === 7 ? 1000 : 1,
        ]);
    }

    $result = app(SeasonalityFactorService::class)->calculateFactor($fixture['company'], $fixture['product'], '2026-07-01', '2026-07-31', [
        'max_factor' => 2.0,
    ]);

    expect($result['factor'])->toBe(2.0)
        ->and($result['warnings'])->toContain('seasonality_factor_clamped');
});

it('category factor calculation uses category history', function (): void {
    $fixture = ForecastingTestSupport::fixture();
    $other = Product::factory()->for($fixture['company'])->create(['category' => $fixture['product']->category]);
    ForecastingTestSupport::seedMonthlyHistory($fixture['company'], $fixture['product']);
    ForecastingTestSupport::seedMonthlyHistory($fixture['company'], $other);

    $result = app(SeasonalityFactorService::class)->calculateCategoryFactor($fixture['company'], $fixture['product']->category, '2026-07-01', '2026-07-31');

    expect($result['factor'])->toBeGreaterThan(1.0)
        ->and($result['explanation']['scope'])->toBe('category');
});
