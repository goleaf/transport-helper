<?php

use App\Models\Permission;
use App\Models\Role;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds the supply role and permission matrix', function () {
    $this->seed(RolePermissionSeeder::class);

    expect(Role::query()->whereIn('name', [
        'admin',
        'supply_manager',
        'logistics_manager',
        'accountant',
        'viewer',
    ])->count())->toBe(5)
        ->and(Permission::query()->whereIn('name', [
            'approve_order_proposals',
            'use_email_form_autofill',
            'apply_email_form_autofill',
            'select_carrier',
            'view_audit_logs',
        ])->count())->toBe(5);

    $permissionsCount = Permission::query()->count();
    $admin = Role::query()->where('name', 'admin')->with('permissions')->firstOrFail();
    $viewer = Role::query()->where('name', 'viewer')->with('permissions')->firstOrFail();
    $supplyManager = Role::query()->where('name', 'supply_manager')->with('permissions')->firstOrFail();
    $logisticsManager = Role::query()->where('name', 'logistics_manager')->with('permissions')->firstOrFail();

    expect($admin->permissions)->toHaveCount($permissionsCount)
        ->and($viewer->permissions->pluck('name'))->not->toContain('approve_order_proposals')
        ->and($supplyManager->permissions->pluck('name'))->toContain('approve_order_proposals')
        ->and($logisticsManager->permissions->pluck('name'))->toContain('select_carrier');
});
