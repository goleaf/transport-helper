<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\SupplierOrder;
use App\Models\User;

class SupplierOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasAnyRole($user, [
            UserRole::Admin,
            UserRole::SupplyManager,
            UserRole::LogisticsManager,
            UserRole::Accountant,
        ]) || $this->hasAnyPermission($user, ['create_supplier_orders', 'view_logistics']);
    }

    public function view(User $user, SupplierOrder $supplierOrder): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->hasAnyRole($user, [UserRole::Admin, UserRole::SupplyManager])
            || $this->hasPermission($user, 'create_supplier_orders');
    }

    public function update(User $user, SupplierOrder $supplierOrder): bool
    {
        return $this->create($user);
    }

    public function approve(User $user, SupplierOrder $supplierOrder): bool
    {
        return $this->create($user);
    }

    public function export(User $user, SupplierOrder $supplierOrder): bool
    {
        return $this->create($user);
    }

    public function prepareEmail(User $user, SupplierOrder $supplierOrder): bool
    {
        return $this->create($user);
    }

    public function approveEmail(User $user, SupplierOrder $supplierOrder): bool
    {
        return $this->hasAnyRole($user, [UserRole::Admin, UserRole::SupplyManager])
            || $this->hasPermission($user, 'approve_supplier_emails');
    }

    public function sendEmail(User $user, SupplierOrder $supplierOrder): bool
    {
        return $this->hasAnyRole($user, [UserRole::Admin, UserRole::SupplyManager])
            || $this->hasPermission($user, 'send_supplier_emails');
    }

    public function delete(User $user, SupplierOrder $supplierOrder): bool
    {
        return false;
    }

    public function restore(User $user, SupplierOrder $supplierOrder): bool
    {
        return false;
    }

    public function forceDelete(User $user, SupplierOrder $supplierOrder): bool
    {
        return false;
    }

    /**
     * @param  list<UserRole>  $roles
     */
    private function hasAnyRole(User $user, array $roles): bool
    {
        return $user->hasAnyRole($roles);
    }

    private function hasRole(User $user, UserRole $role): bool
    {
        return $user->hasRole($role);
    }

    private function hasPermission(User $user, string $permission): bool
    {
        return method_exists($user, 'hasPermission')
            ? $user->hasPermission($permission)
            : (method_exists($user, 'hasPermissionTo') && $user->hasPermissionTo($permission));
    }

    /**
     * @param  list<string>  $permissions
     */
    private function hasAnyPermission(User $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($user, $permission)) {
                return true;
            }
        }

        return false;
    }
}
