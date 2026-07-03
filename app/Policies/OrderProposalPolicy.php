<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\OrderProposal;
use App\Models\User;

class OrderProposalPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasAnyRole($user, [
            UserRole::Admin,
            UserRole::SupplyManager,
            UserRole::LogisticsManager,
            UserRole::Accountant,
            UserRole::Viewer,
        ]) || $this->hasAnyPermission($user, ['view_calculations', 'approve_order_proposals']);
    }

    public function view(User $user, OrderProposal $orderProposal): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->approve($user, new OrderProposal);
    }

    public function update(User $user, OrderProposal $orderProposal): bool
    {
        return $this->approve($user, $orderProposal);
    }

    public function approve(User $user, OrderProposal $orderProposal): bool
    {
        return $this->hasAnyRole($user, [UserRole::Admin, UserRole::SupplyManager])
            || $this->hasPermission($user, 'approve_order_proposals');
    }

    public function convertToSupplierOrder(User $user, OrderProposal $orderProposal): bool
    {
        return $this->hasAnyRole($user, [UserRole::Admin, UserRole::SupplyManager])
            || $this->hasPermission($user, 'create_supplier_orders');
    }

    public function delete(User $user, OrderProposal $orderProposal): bool
    {
        return false;
    }

    public function restore(User $user, OrderProposal $orderProposal): bool
    {
        return false;
    }

    public function forceDelete(User $user, OrderProposal $orderProposal): bool
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
