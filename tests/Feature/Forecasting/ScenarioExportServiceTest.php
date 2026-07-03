<?php

use App\Models\AuditLog;
use App\Models\CalculationScenario;
use App\Models\CalculationScenarioItem;
use App\Models\ExportFile;
use App\Services\Supply\Forecasting\ScenarioExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Support\ForecastingTestSupport;

uses(RefreshDatabase::class);

it('exports scenario to csv and creates export file', function (): void {
    Storage::fake();
    $fixture = ForecastingTestSupport::fixture();
    $scenario = CalculationScenario::factory()->for($fixture['company'])->for($fixture['supplier'])->create(['status' => 'simulated']);
    CalculationScenarioItem::factory()->for($scenario, 'scenario')->for($fixture['product'])->create(['simulated_recommended_quantity' => 120]);

    $result = app(ScenarioExportService::class)->exportCsv($scenario, $fixture['user']);

    Storage::assertExists($result['path']);
    expect(ExportFile::query()->where('export_type', 'scenario_csv')->exists())->toBeTrue()
        ->and(Storage::get($result['path']))->toContain($fixture['product']->sku);
});

it('exports scenario to detail file and audits', function (): void {
    Storage::fake();
    $fixture = ForecastingTestSupport::fixture();
    $scenario = CalculationScenario::factory()->for($fixture['company'])->for($fixture['supplier'])->create(['status' => 'simulated']);
    CalculationScenarioItem::factory()->for($scenario, 'scenario')->for($fixture['product'])->create(['simulated_recommended_quantity' => 120]);

    $result = app(ScenarioExportService::class)->exportJson($scenario, $fixture['user']);

    Storage::assertExists($result['path']);
    expect(ExportFile::query()->where('export_type', 'scenario_json')->exists())->toBeTrue()
        ->and(AuditLog::query()->where('event_type', 'scenario_exported')->exists())->toBeTrue();
});
