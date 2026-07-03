<?php

use App\Enums\ReplenishmentProfileStatus;
use App\Models\AuditLog;
use App\Models\ReplenishmentProfile;
use App\Services\Supply\Forecasting\ReplenishmentProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ForecastingTestSupport;

uses(RefreshDatabase::class);

it('creates updates and archives profile', function (): void {
    $fixture = ForecastingTestSupport::fixture();
    $service = app(ReplenishmentProfileService::class);
    $created = $service->createProfile([
        'company_id' => $fixture['company']->id,
        'product_id' => $fixture['product']->id,
        'name' => 'Product safety profile',
        'priority' => 10,
    ], $fixture['user'])['profile'];

    expect($created->exists)->toBeTrue()
        ->and($created->priority)->toBe(10);

    $updated = $service->updateProfile($created, ['name' => 'Updated profile'], $fixture['user'])['profile'];
    expect($updated->name)->toBe('Updated profile');

    $archived = $service->archiveProfile($updated, $fixture['user'], 'No longer needed.')['profile'];
    expect($archived->status)->toBe(ReplenishmentProfileStatus::Archived)
        ->and($archived->is_active)->toBeFalse();
});

it('profile priority and audit are written', function (): void {
    $fixture = ForecastingTestSupport::fixture();

    ReplenishmentProfile::factory()->for($fixture['company'])->create(['priority' => 50]);
    app(ReplenishmentProfileService::class)->createProfile([
        'company_id' => $fixture['company']->id,
        'name' => 'Company default',
        'priority' => 5,
    ], $fixture['user']);

    $profiles = app(ReplenishmentProfileService::class)->activeProfiles($fixture['company']);

    expect($profiles[0]->priority)->toBe(5)
        ->and(AuditLog::query()->where('event_type', 'replenishment_profile_created')->exists())->toBeTrue();
});
