<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'view_products',
            'manage_products',
            'import_data',
            'view_calculations',
            'run_calculations',
            'approve_order_proposals',
            'adjust_order_quantities',
            'create_supplier_orders',
            'approve_supplier_emails',
            'send_supplier_emails',
            'view_supplier_confirmations',
            'apply_supplier_confirmations',
            'manage_transport',
            'select_carrier',
            'view_logistics',
            'manage_logistics',
            'view_audit_logs',
            'manage_integrations',
            'manage_settings',
            'review_ai_extractions',
            'use_email_form_autofill',
            'apply_email_form_autofill',
            'view_analytics',
            'export_analytics',
            'manage_saved_reports',
        ];

        $permissionModels = collect($permissions)
            ->mapWithKeys(fn (string $permission) => [
                $permission => Permission::query()->updateOrCreate(
                    ['name' => $permission],
                    ['label' => ucwords(str_replace('_', ' ', $permission))]
                ),
            ]);

        $rolePermissions = [
            'admin' => $permissions,
            'supply_manager' => [
                'view_products',
                'manage_products',
                'import_data',
                'view_calculations',
                'run_calculations',
                'approve_order_proposals',
                'adjust_order_quantities',
                'create_supplier_orders',
                'approve_supplier_emails',
                'send_supplier_emails',
                'view_supplier_confirmations',
                'apply_supplier_confirmations',
                'view_logistics',
                'view_audit_logs',
                'review_ai_extractions',
                'use_email_form_autofill',
                'apply_email_form_autofill',
                'view_analytics',
                'export_analytics',
                'manage_saved_reports',
            ],
            'logistics_manager' => [
                'view_products',
                'view_supplier_confirmations',
                'manage_transport',
                'select_carrier',
                'view_logistics',
                'manage_logistics',
                'review_ai_extractions',
                'use_email_form_autofill',
                'apply_email_form_autofill',
                'view_analytics',
                'export_analytics',
            ],
            'accountant' => [
                'view_products',
                'view_calculations',
                'view_supplier_confirmations',
                'view_logistics',
                'view_audit_logs',
                'view_analytics',
            ],
            'viewer' => [
                'view_products',
                'view_calculations',
                'view_supplier_confirmations',
                'view_logistics',
            ],
        ];

        foreach ($rolePermissions as $roleName => $permissionNames) {
            $role = Role::query()->updateOrCreate(
                ['name' => $roleName],
                ['label' => ucwords(str_replace('_', ' ', $roleName))]
            );

            $role->permissions()->sync(
                collect($permissionNames)
                    ->map(fn (string $permissionName) => $permissionModels[$permissionName]->getKey())
                    ->all()
            );
        }
    }
}
