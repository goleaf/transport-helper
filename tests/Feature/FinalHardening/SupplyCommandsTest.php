<?php

use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\LogisticsTestSupport;

uses(RefreshDatabase::class);

it('supply health check runs', function (): void {
    LogisticsTestSupport::fixture();

    $this->artisan('supply:health-check')->assertExitCode(0);
});

it('supply monitor logistics dry run runs', function (): void {
    LogisticsTestSupport::fixture();

    $this->artisan('supply:monitor-logistics --dry-run')->assertExitCode(0);
});

it('supply permissions audit runs', function (): void {
    $this->seed(RolePermissionSeeder::class);

    $this->artisan('supply:permissions-audit')->assertExitCode(0);
});

it('supply audit coverage runs', function (): void {
    $this->artisan('supply:audit-coverage')->assertExitCode(0);
});

it('supply backup verify runs', function (): void {
    $this->artisan('supply:backup-verify')->assertExitCode(0);
});

it('supply ai boundary audit runs', function (): void {
    $this->artisan('supply:ai-boundary-audit')->assertExitCode(0);
});

it('supply production readiness runs', function (): void {
    $this->seed(RolePermissionSeeder::class);

    $this->artisan('supply:production-readiness')->assertExitCode(0);
});
