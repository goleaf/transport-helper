<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\ProcurementException;
use App\Models\User;

class ProcurementExceptionPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canView($user);
    }

    public function view(User $user, ProcurementException $exception): bool
    {
        return $this->canView($user) || (int) $exception->requested_by_user_id === (int) $user->getKey();
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([UserRole::Admin, UserRole::SupplyManager])
            || $user->hasPermissionTo('create_supplier_orders')
            || $user->hasPermissionTo('approve_order_proposals');
    }

    public function decide(User $user, ProcurementException $exception): bool
    {
        return $user->hasRole(UserRole::Admin)
            || $user->hasPermissionTo('manage_settings')
            || $user->hasPermissionTo('approve_order_proposals');
    }

    private function canView(User $user): bool
    {
        return $user->hasAnyRole([UserRole::Admin, UserRole::SupplyManager, UserRole::Accountant])
            || $user->hasPermissionTo('view_analytics')
            || $user->hasPermissionTo('view_audit_logs');
    }
}
