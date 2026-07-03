<?php

use App\Models\AuditLog;
use App\Models\CalculationScenario;
use App\Models\OrderProposal;
use App\Models\SupplierOrder;
use App\Services\Supply\Forecasting\ScenarioSimulationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ForecastingTestSupport;

uses(RefreshDatabase::class);

it('simulates scenario for supplier and creates scenario items', function (): void {
    $fixture = ForecastingTestSupport::fixture();
    ForecastingTestSupport::seedCalculationSales($fixture['company'], $fixture['product']);

    $result = app(ScenarioSimulationService::class)->simulate($fixture['company'], $fixture['supplier'], ForecastingTestSupport::parameters(), $fixture['user']);

    expect($result['scenario'])->toBeInstanceOf(CalculationScenario::class)
        ->and($result['scenario']->items)->toHaveCount(1)
        ->and($result['scenario']->summary_json['items_count'])->toBe(1);
});

it('scenario does not create order proposal or supplier order and audit is written', function (): void {
    $fixture = ForecastingTestSupport::fixture();
    ForecastingTestSupport::seedCalculationSales($fixture['company'], $fixture['product']);

    app(ScenarioSimulationService::class)->simulate($fixture['company'], $fixture['supplier'], ForecastingTestSupport::parameters(), $fixture['user']);

    expect(OrderProposal::query()->count())->toBe(0)
        ->and(SupplierOrder::query()->count())->toBe(0)
        ->and(AuditLog::query()->where('event_type', 'scenario_simulation_completed')->exists())->toBeTrue();
});

it('scenario with missing data is simulated with warnings and uses calculator output', function (): void {
    $fixture = ForecastingTestSupport::fixture();

    $result = app(ScenarioSimulationService::class)->simulate($fixture['company'], $fixture['supplier'], ForecastingTestSupport::parameters(), $fixture['user']);

    expect($result['scenario']->warnings_json)->not->toBeEmpty()
        ->and($result['scenario']->items->first()->output_json)->toHaveKey('formula_version');
});
