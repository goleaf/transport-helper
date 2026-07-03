<?php

use App\Models\Permission;
use App\Models\Role;
use App\Services\Supply\Security\PermissionAuditService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns permission audit structure', function (): void {
    $result = app(PermissionAuditService::class)->run();

    expect($result)->toHaveKeys([
        'status',
        'checks',
        'expected_roles',
        'expected_permissions',
        'missing_roles',
        'missing_permissions',
        'dangerous_assignments',
        'missing_policies',
    ]);
});

it('lists the critical permissions expected by the supply workflow', function (): void {
    $permissions = app(PermissionAuditService::class)->expectedPermissions();

    expect($permissions)->toContain(
        'approve_order_proposals',
        'send_supplier_emails',
        'apply_supplier_confirmations',
        'select_carrier',
        'manage_logistics',
        'view_audit_logs',
    );
});

it('passes seeded role and permission matrix checks', function (): void {
    $this->seed(RolePermissionSeeder::class);

    $result = app(PermissionAuditService::class)->run();

    expect($result['missing_roles'])->toBe([])
        ->and($result['missing_permissions'])->toBe([])
        ->and($result['dangerous_assignments'])->toBe([])
        ->and(Role::query()->where('name', 'admin')->firstOrFail()->permissions)->toHaveCount(Permission::query()->count());
});

it('reports viewer dangerous permissions if they are assigned', function (): void {
    $this->seed(RolePermissionSeeder::class);

    $viewer = Role::query()->where('name', 'viewer')->firstOrFail();
    $permission = Permission::query()->where('name', 'select_carrier')->firstOrFail();
    $viewer->permissions()->syncWithoutDetaching([$permission->id]);

    $result = app(PermissionAuditService::class)->run();

    expect($result['status'])->toBe('error')
        ->and($result['dangerous_assignments'])->not->toBeEmpty();
});

it('permission audit command supports json output', function (): void {
    $this->seed(RolePermissionSeeder::class);

    $this->artisan('supply:permissions-audit --json')
        ->expectsOutputToContain('"status"')
        ->assertExitCode(0);
});
