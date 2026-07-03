<?php

use App\Models\SavedReport;
use App\Models\User;
use App\Services\Supply\Analytics\SavedReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\AnalyticsTestSupport;

uses(RefreshDatabase::class);

it('creates updates lists and defaults saved reports', function (): void {
    $fixture = AnalyticsTestSupport::fixture();
    $service = app(SavedReportService::class);

    $created = $service->create([
        'name' => 'Supplier performance weekly',
        'report_type' => 'supplier_performance',
        'filters_json' => ['supplier_id' => $fixture['supplier']->id],
        'is_shared' => false,
    ], $fixture['user']);

    $service->update($created['report'], ['name' => 'Updated report'], $fixture['user']);
    $service->setDefault($created['report']->fresh(), $fixture['user']);

    expect(SavedReport::query()->where('name', 'Updated report')->exists())->toBeTrue()
        ->and($service->list($fixture['user'], 'supplier_performance'))->toHaveCount(1)
        ->and($created['report']->fresh()->is_default)->toBeTrue();
});

it('keeps private reports visible only to owner unless shared', function (): void {
    $fixture = AnalyticsTestSupport::fixture();
    $other = User::factory()->create(['role' => 'supply_manager']);
    $service = app(SavedReportService::class);

    $service->create([
        'name' => 'Private report',
        'report_type' => 'stockout_risk',
        'is_shared' => false,
    ], $fixture['user']);

    expect($service->list($other))->toBeEmpty();
});
