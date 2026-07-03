<?php

namespace App\Services\Supply\Security;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\Schema;

class PermissionAuditService
{
    /**
     * @return list<string>
     */
    public function expectedRoles(): array
    {
        return [
            'admin',
            'supply_manager',
            'logistics_manager',
            'accountant',
            'viewer',
        ];
    }

    /**
     * @return list<string>
     */
    public function expectedPermissions(): array
    {
        return [
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
    }

    /**
     * @return array<string, mixed>
     */
    public function run(): array
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('permissions')) {
            return [
                'status' => 'error',
                'checks' => [
                    $this->check('role_permission_tables', 'error', 'Role or permission table is missing.'),
                ],
                'expected_roles' => $this->expectedRoles(),
                'expected_permissions' => $this->expectedPermissions(),
                'missing_roles' => $this->expectedRoles(),
                'missing_permissions' => $this->expectedPermissions(),
                'dangerous_assignments' => [],
                'missing_policies' => $this->expectedPolicies(),
            ];
        }

        $existingRoles = Role::query()
            ->whereIn('name', $this->expectedRoles())
            ->pluck('name')
            ->all();
        $existingPermissions = Permission::query()
            ->whereIn('name', $this->expectedPermissions())
            ->pluck('name')
            ->all();

        $missingRoles = array_values(array_diff($this->expectedRoles(), $existingRoles));
        $missingPermissions = array_values(array_diff($this->expectedPermissions(), $existingPermissions));
        $dangerousAssignments = $this->dangerousAssignments();
        $missingPolicies = $this->missingPolicies();

        $checks = [
            $this->check('expected_roles', $missingRoles === [] ? 'ok' : 'error', $missingRoles === [] ? 'All expected roles exist.' : 'Some expected roles are missing.', ['missing' => $missingRoles]),
            $this->check('expected_permissions', $missingPermissions === [] ? 'ok' : 'error', $missingPermissions === [] ? 'All expected permissions exist.' : 'Some expected permissions are missing.', ['missing' => $missingPermissions]),
            $this->check('admin_permissions', $this->adminHasAllPermissions() ? 'ok' : 'error', $this->adminHasAllPermissions() ? 'Admin has every permission.' : 'Admin is missing permissions.'),
            $this->check('dangerous_assignments', $dangerousAssignments === [] ? 'ok' : 'error', $dangerousAssignments === [] ? 'Viewer/accountant dangerous permissions are not assigned.' : 'Dangerous permissions are assigned to read-only roles.', ['assignments' => $dangerousAssignments]),
            $this->check('policies', $missingPolicies === [] ? 'ok' : 'warning', $missingPolicies === [] ? 'Expected policies exist.' : 'Some expected policies are missing.', ['missing' => $missingPolicies]),
        ];

        return [
            'status' => $this->statusFromChecks($checks),
            'checks' => $checks,
            'expected_roles' => $this->expectedRoles(),
            'expected_permissions' => $this->expectedPermissions(),
            'missing_roles' => $missingRoles,
            'missing_permissions' => $missingPermissions,
            'dangerous_assignments' => $dangerousAssignments,
            'missing_policies' => $missingPolicies,
        ];
    }

    /**
     * @return list<string>
     */
    private function expectedPolicies(): array
    {
        return [
            'OrderProposalPolicy',
            'SupplierOrderPolicy',
            'EmailMessagePolicy',
            'AiEmailExtractionPolicy',
            'FormAutofillRunPolicy',
            'SupplierConfirmationPolicy',
            'CarrierQuotePolicy',
            'LogisticsRecordPolicy',
            'ImportBatchPolicy',
            'AuditLogPolicy',
            'IntegrationConnectionPolicy',
        ];
    }

    /**
     * @return list<array{role:string, permission:string}>
     */
    private function dangerousAssignments(): array
    {
        $dangerous = [
            'approve_order_proposals',
            'adjust_order_quantities',
            'create_supplier_orders',
            'approve_supplier_emails',
            'send_supplier_emails',
            'apply_supplier_confirmations',
            'manage_transport',
            'select_carrier',
            'manage_logistics',
            'manage_integrations',
            'manage_settings',
            'apply_email_form_autofill',
        ];

        return Role::query()
            ->whereIn('name', ['viewer', 'accountant'])
            ->with(['permissions:id,name'])
            ->get(['id', 'name'])
            ->flatMap(fn (Role $role) => $role->permissions
                ->whereIn('name', $dangerous)
                ->map(fn (Permission $permission): array => [
                    'role' => (string) $role->name,
                    'permission' => (string) $permission->name,
                ]))
            ->values()
            ->all();
    }

    private function adminHasAllPermissions(): bool
    {
        $admin = Role::query()
            ->where('name', 'admin')
            ->with(['permissions:id,name'])
            ->first();

        if (! $admin instanceof Role) {
            return false;
        }

        $permissionNames = $admin->permissions->pluck('name')->all();

        return array_diff($this->expectedPermissions(), $permissionNames) === [];
    }

    /**
     * @return list<string>
     */
    private function missingPolicies(): array
    {
        return collect($this->expectedPolicies())
            ->reject(fn (string $policy): bool => is_file(app_path('Policies/'.$policy.'.php')))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    private function check(string $name, string $status, string $message, array $metadata = []): array
    {
        return compact('name', 'status', 'message', 'metadata');
    }

    /**
     * @param  list<array<string, mixed>>  $checks
     */
    private function statusFromChecks(array $checks): string
    {
        if (collect($checks)->contains(fn (array $check): bool => $check['status'] === 'error')) {
            return 'error';
        }

        if (collect($checks)->contains(fn (array $check): bool => $check['status'] === 'warning')) {
            return 'warning';
        }

        return 'ok';
    }
}
