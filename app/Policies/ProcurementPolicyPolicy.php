<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\ProcurementPolicy;
use App\Models\User;

class ProcurementPolicyPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canView($user);
    }

    public function view(User $user, ProcurementPolicy $policy): bool
    {
        return $this->canView($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, ProcurementPolicy $policy): bool
    {
        return $this->canManage($user);
    }

    public function archive(User $user, ProcurementPolicy $policy): bool
    {
        return $this->canManage($user);
    }

    private function canView(User $user): bool
    {
        return $user->hasAnyRole([UserRole::Admin, UserRole::SupplyManager, UserRole::Accountant, UserRole::Viewer])
            || $user->hasPermissionTo('view_analytics')
            || $user->hasPermissionTo('view_calculations');
    }

    private function canManage(User $user): bool
    {
        return $user->hasRole(UserRole::Admin) || $user->hasPermissionTo('manage_settings');
    }
}
