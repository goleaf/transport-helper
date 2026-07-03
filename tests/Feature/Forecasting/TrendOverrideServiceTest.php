<?php

use App\Enums\TrendOverrideStatus;
use App\Models\AuditLog;
use App\Models\TrendOverride;
use App\Services\Supply\Forecasting\TrendOverrideService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ForecastingTestSupport;

uses(RefreshDatabase::class);

it('create override requires reason', function (): void {
    $fixture = ForecastingTestSupport::fixture();

    app(TrendOverrideService::class)->createOverride([
        'company_id' => $fixture['company']->id,
        'product_id' => $fixture['product']->id,
        'trend_value' => 1.2,
        'date_from' => '2026-06-01',
        'date_to' => '2026-07-01',
        'reason' => '',
    ], $fixture['user']);
})->throws(InvalidArgumentException::class);

it('override requires approval before use and rejected override is not used', function (): void {
    $fixture = ForecastingTestSupport::fixture();
    $service = app(TrendOverrideService::class);
    $override = TrendOverride::factory()->for($fixture['company'])->for($fixture['product'])->create([
        'status' => TrendOverrideStatus::PendingApproval,
        'date_from' => '2026-06-01',
        'date_to' => '2026-07-01',
    ]);

    expect($service->findApplicable($fixture['company'], $fixture['product'], $fixture['supplier'], '2026-06-15')['usable'])->toBeFalse();

    $service->reject($override, $fixture['user'], 'Not reliable.');

    expect($service->findApplicable($fixture['company'], $fixture['product'], $fixture['supplier'], '2026-06-15')['usable'])->toBeFalse();
});

it('approves rejects and revokes override with audit', function (): void {
    $fixture = ForecastingTestSupport::fixture();
    $service = app(TrendOverrideService::class);
    $override = TrendOverride::factory()->for($fixture['company'])->for($fixture['product'])->create([
        'status' => TrendOverrideStatus::PendingApproval,
        'date_from' => '2026-06-01',
        'date_to' => '2026-07-01',
    ]);

    $service->approve($override, $fixture['user'], 'Approved by test.');
    $override->refresh();

    expect($override->status)->toBe(TrendOverrideStatus::Approved)
        ->and($service->findApplicable($fixture['company'], $fixture['product'], $fixture['supplier'], '2026-06-15')['usable'])->toBeTrue();

    $service->revoke($override, $fixture['user'], 'No longer valid.');

    expect($override->refresh()->status)->toBe(TrendOverrideStatus::Revoked)
        ->and(AuditLog::query()->where('event_type', 'trend_override_revoked')->exists())->toBeTrue();
});

it('find applicable prefers product override', function (): void {
    $fixture = ForecastingTestSupport::fixture();
    TrendOverride::factory()->for($fixture['company'])->create([
        'category' => $fixture['product']->category,
        'trend_value' => 1.1,
        'status' => TrendOverrideStatus::Approved,
        'approved_by_user_id' => $fixture['user']->id,
        'approved_at' => now(),
        'date_from' => '2026-06-01',
        'date_to' => '2026-07-01',
    ]);
    $productOverride = TrendOverride::factory()->for($fixture['company'])->for($fixture['product'])->create([
        'trend_value' => 1.5,
        'status' => TrendOverrideStatus::Approved,
        'approved_by_user_id' => $fixture['user']->id,
        'approved_at' => now(),
        'date_from' => '2026-06-01',
        'date_to' => '2026-07-01',
    ]);

    $result = app(TrendOverrideService::class)->findApplicable($fixture['company'], $fixture['product'], $fixture['supplier'], '2026-06-15');

    expect($result['override']->id)->toBe($productOverride->id);
});
