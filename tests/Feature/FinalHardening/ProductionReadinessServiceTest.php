<?php

use App\Services\Supply\Security\ProductionReadinessService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('aggregates production readiness sections', function (): void {
    $this->seed(RolePermissionSeeder::class);

    $result = app(ProductionReadinessService::class)->run();

    expect($result)->toHaveKeys(['status', 'sections', 'summary'])
        ->and($result['sections'])->toHaveKeys(['health', 'security', 'permissions', 'audit', 'backup', 'ai_boundary', 'boundaries']);
});

it('returns an overall status', function (): void {
    $result = app(ProductionReadinessService::class)->run();

    expect($result['status'])->toBeIn(['ok', 'warning', 'error']);
});

it('strict mode escalates warnings to a strict failure marker', function (): void {
    config()->set('supply.backup.marker_path', storage_path('app/backups/missing-marker.txt'));

    $result = app(ProductionReadinessService::class)->run(['strict' => true]);

    expect($result)->toHaveKey('strict_failed')
        ->and($result['strict_failed'])->toBeTrue();
});

it('production readiness command supports json output', function (): void {
    $this->seed(RolePermissionSeeder::class);

    $this->artisan('supply:production-readiness --json')
        ->expectsOutputToContain('"sections"')
        ->assertExitCode(0);
});

it('does not expose secret values in production readiness output', function (): void {
    config()->set('services.fake.secret', 'super-secret-value');

    $result = app(ProductionReadinessService::class)->run();

    expect(json_encode($result))->not->toContain('super-secret-value');
});
