<?php

use App\Enums\TrendOverrideStatus;
use App\Models\CalculationScenario;
use App\Models\ReplenishmentProfile;
use App\Models\SalesExclusionRule;
use App\Models\TrendOverride;
use App\Models\User;
use App\Services\Supply\Forecasting\ScenarioSimulationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Support\ForecastingTestSupport;

uses(RefreshDatabase::class);

it('profiles index loads and creates profile', function (): void {
    $fixture = ForecastingTestSupport::fixture();

    $this->actingAs($fixture['user'])
        ->get(route('supply.forecasting.profiles.index'))
        ->assertOk()
        ->assertSee('Replenishment Profiles');

    $this->actingAs($fixture['user'])
        ->post(route('supply.forecasting.profiles.store'), [
            'company_id' => $fixture['company']->id,
            'product_id' => $fixture['product']->id,
            'name' => 'Controller profile',
            'priority' => 10,
        ])
        ->assertRedirect();

    expect(ReplenishmentProfile::query()->where('name', 'Controller profile')->exists())->toBeTrue();
});

it('creates exclusion rule and trend override through routes', function (): void {
    $fixture = ForecastingTestSupport::fixture();

    $this->actingAs($fixture['user'])
        ->post(route('supply.forecasting.exclusions.store'), [
            'company_id' => $fixture['company']->id,
            'product_id' => $fixture['product']->id,
            'rule_type' => 'manual_exclusion',
            'date_from' => '2026-06-01',
            'date_to' => '2026-06-30',
            'applies_to' => 'all_calculation_periods',
            'reason' => 'Controller exclusion reason.',
            'is_active' => true,
        ])
        ->assertRedirect();

    $this->actingAs($fixture['user'])
        ->post(route('supply.forecasting.overrides.store'), [
            'company_id' => $fixture['company']->id,
            'product_id' => $fixture['product']->id,
            'trend_value' => 1.3,
            'date_from' => '2026-06-01',
            'date_to' => '2026-07-30',
            'reason' => 'Controller trend reason.',
        ])
        ->assertRedirect();

    expect(SalesExclusionRule::query()->exists())->toBeTrue()
        ->and(TrendOverride::query()->exists())->toBeTrue();
});

it('approves trend override route and blocks viewer approval', function (): void {
    $fixture = ForecastingTestSupport::fixture();
    $override = TrendOverride::factory()->for($fixture['company'])->for($fixture['product'])->create([
        'status' => TrendOverrideStatus::PendingApproval,
    ]);

    $this->actingAs($fixture['user'])
        ->post(route('supply.forecasting.overrides.approve', $override), ['note' => 'Looks good.'])
        ->assertRedirect();

    expect($override->refresh()->status)->toBe(TrendOverrideStatus::Approved);

    $viewer = User::factory()->create(['role' => 'viewer']);
    $this->actingAs($viewer)
        ->post(route('supply.forecasting.overrides.approve', $override), ['note' => 'Viewer approval.'])
        ->assertForbidden();
});

it('scenario create simulate show and export routes work', function (): void {
    Storage::fake();
    $fixture = ForecastingTestSupport::fixture();
    ForecastingTestSupport::seedCalculationSales($fixture['company'], $fixture['product']);

    $this->actingAs($fixture['user'])
        ->get(route('supply.forecasting.scenarios.create'))
        ->assertOk()
        ->assertSee('Run Calculation Scenario');

    $this->actingAs($fixture['user'])
        ->post(route('supply.forecasting.scenarios.simulate'), [
            'company_id' => $fixture['company']->id,
            'supplier_id' => $fixture['supplier']->id,
            'name' => 'Controller scenario',
            't0_date' => '2026-07-01',
            't1_date' => '2026-07-15',
            't2_date' => '2026-08-14',
            't3_date' => '2026-09-01',
            'scenario_options' => [
                'exclude_promotions' => true,
                'exclude_anomalies' => true,
            ],
        ])
        ->assertRedirect();

    $scenario = CalculationScenario::query()->latest('id')->firstOrFail();

    $this->actingAs($fixture['user'])
        ->get(route('supply.forecasting.scenarios.show', $scenario))
        ->assertOk()
        ->assertSee('Recommended quantity changes');

    $this->actingAs($fixture['user'])
        ->post(route('supply.forecasting.scenarios.export', $scenario), ['format' => 'csv'])
        ->assertRedirect();
});

it('scenario comparison route renders comparison', function (): void {
    $fixture = ForecastingTestSupport::fixture();
    ForecastingTestSupport::seedCalculationSales($fixture['company'], $fixture['product']);
    $service = app(ScenarioSimulationService::class);
    $a = $service->simulate($fixture['company'], $fixture['supplier'], ForecastingTestSupport::parameters(['name' => 'Scenario A']), $fixture['user'])['scenario'];
    $b = $service->simulate($fixture['company'], $fixture['supplier'], ForecastingTestSupport::parameters(['name' => 'Scenario B']), $fixture['user'])['scenario'];

    $this->actingAs($fixture['user'])
        ->post(route('supply.forecasting.scenarios.compare'), [
            'scenario_a_id' => $a->id,
            'scenario_b_id' => $b->id,
        ])
        ->assertOk()
        ->assertSee('Scenario Comparison');
});
