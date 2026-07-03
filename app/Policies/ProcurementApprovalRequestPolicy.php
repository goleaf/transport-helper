<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\ProcurementApprovalRequest;
use App\Models\User;

class ProcurementApprovalRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canView($user);
    }

    public function view(User $user, ProcurementApprovalRequest $request): bool
    {
        return $this->canView($user) || (int) $request->requested_by_user_id === (int) $user->getKey();
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([UserRole::Admin, UserRole::SupplyManager])
            || $user->hasPermissionTo('create_supplier_orders')
            || $user->hasPermissionTo('approve_order_proposals');
    }

    public function decide(User $user, ProcurementApprovalRequest $request): bool
    {
        return $user->hasRole(UserRole::Admin)
            || $user->hasPermissionTo('manage_settings')
            || $user->hasPermissionTo('approve_order_proposals')
            || ($request->required_permission !== null && $user->hasPermissionTo($request->required_permission))
            || ($request->required_role !== null && $user->hasRole($request->required_role));
    }

    private function canView(User $user): bool
    {
        return $user->hasAnyRole([UserRole::Admin, UserRole::SupplyManager, UserRole::Accountant])
            || $user->hasPermissionTo('view_analytics')
            || $user->hasPermissionTo('view_audit_logs');
    }
}
