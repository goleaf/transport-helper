<?php

use App\Models\ReplenishmentProfile;
use App\Services\Supply\Forecasting\ReplenishmentRuleResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ForecastingTestSupport;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('product profile overrides category profile', function (): void {
    $fixture = ForecastingTestSupport::fixture();
    ReplenishmentProfile::factory()->for($fixture['company'])->create([
        'category' => $fixture['product']->category,
        'name' => 'Category profile',
        'safety_days_override' => 7,
    ]);
    $productProfile = ReplenishmentProfile::factory()->for($fixture['company'])->for($fixture['product'])->create([
        'name' => 'Product profile',
        'safety_days_override' => 21,
    ]);

    $resolved = app(ReplenishmentRuleResolver::class)->resolve($fixture['company'], $fixture['product'], $fixture['supplier']);

    expect($resolved['profile']->id)->toBe($productProfile->id)
        ->and($resolved['rules']['safety_days_override'])->toBe(21);
});

it('supplier category profile overrides company default', function (): void {
    $fixture = ForecastingTestSupport::fixture();
    ReplenishmentProfile::factory()->for($fixture['company'])->create(['name' => 'Default', 'safety_days_override' => 5]);
    $supplierCategory = ReplenishmentProfile::factory()->for($fixture['company'])->for($fixture['supplier'])->create([
        'category' => $fixture['product']->category,
        'safety_days_override' => 17,
    ]);

    $resolved = app(ReplenishmentRuleResolver::class)->resolve($fixture['company'], $fixture['product'], $fixture['supplier']);

    expect($resolved['profile']->id)->toBe($supplierCategory->id)
        ->and($resolved['rules']['safety_days_override'])->toBe(17);
});

it('supplier profile and safe defaults are used appropriately', function (): void {
    $fixture = ForecastingTestSupport::fixture();
    $supplierProfile = ReplenishmentProfile::factory()->for($fixture['company'])->for($fixture['supplier'])->create([
        'lead_time_days_override' => 33,
    ]);

    $resolved = app(ReplenishmentRuleResolver::class)->resolve($fixture['company'], $fixture['product'], $fixture['supplier']);

    expect($resolved['profile']->id)->toBe($supplierProfile->id)
        ->and($resolved['rules']['lead_time_days_override'])->toBe(33)
        ->and($resolved['explanation'])->not->toBeEmpty();

    ReplenishmentProfile::query()->delete();

    $defaults = app(ReplenishmentRuleResolver::class)->resolve($fixture['company'], $fixture['product'], $fixture['supplier']);
    expect($defaults['profile'])->toBeNull()
        ->and($defaults['rules']['safety_days_override'])->toBe(14);
});
