<?php

use App\Models\CalculationScenario;
use App\Models\CalculationScenarioItem;
use App\Models\OrderProposal;
use App\Models\OrderProposalItem;
use App\Services\Supply\Forecasting\ScenarioComparisonService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ForecastingTestSupport;

uses(RefreshDatabase::class);

it('compares two scenarios and detects quantity direction', function (): void {
    $fixture = ForecastingTestSupport::fixture();
    $scenarioA = CalculationScenario::factory()->for($fixture['company'])->for($fixture['supplier'])->create();
    $scenarioB = CalculationScenario::factory()->for($fixture['company'])->for($fixture['supplier'])->create();
    CalculationScenarioItem::factory()->for($scenarioA, 'scenario')->for($fixture['product'])->create(['simulated_recommended_quantity' => 100]);
    CalculationScenarioItem::factory()->for($scenarioB, 'scenario')->for($fixture['product'])->create(['simulated_recommended_quantity' => 120]);

    $comparison = app(ScenarioComparisonService::class)->compare($scenarioA, $scenarioB);

    expect($comparison['summary']['increased_count'])->toBe(1)
        ->and($comparison['summary']['total_quantity_difference'])->toBe(20.0);
});

it('compare with proposal and warns when products are missing from one side', function (): void {
    $fixture = ForecastingTestSupport::fixture();
    $scenario = CalculationScenario::factory()->for($fixture['company'])->for($fixture['supplier'])->create();
    CalculationScenarioItem::factory()->for($scenario, 'scenario')->for($fixture['product'])->create(['simulated_recommended_quantity' => 120]);
    $proposal = OrderProposal::factory()->for($fixture['company'])->for($fixture['supplier'])->create();
    OrderProposalItem::factory()->for($proposal, 'orderProposal')->for($fixture['product'])->create(['recommended_quantity' => 100]);

    $comparison = app(ScenarioComparisonService::class)->compareWithProposal($scenario, $proposal);

    expect($comparison['summary']['items_compared'])->toBe(1)
        ->and($comparison['rows'][0]['difference'])->toBe(20.0);
});
