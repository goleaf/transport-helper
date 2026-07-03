<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\TrendOverride;
use App\Models\User;

class TrendOverridePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canView($user);
    }

    public function view(User $user, TrendOverride $override): bool
    {
        return $this->canView($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, TrendOverride $override): bool
    {
        return $this->canManage($user);
    }

    public function submit(User $user, TrendOverride $override): bool
    {
        return $this->canManage($user);
    }

    public function approve(User $user, TrendOverride $override): bool
    {
        return $this->canApprove($user);
    }

    public function reject(User $user, TrendOverride $override): bool
    {
        return $this->canApprove($user);
    }

    public function revoke(User $user, TrendOverride $override): bool
    {
        return $this->canApprove($user);
    }

    private function canView(User $user): bool
    {
        return $user->hasAnyRole([UserRole::Admin, UserRole::SupplyManager, UserRole::Viewer])
            || $user->hasPermissionTo('view_calculations')
            || $user->hasPermissionTo('view_analytics');
    }

    private function canManage(User $user): bool
    {
        return $user->hasAnyRole([UserRole::Admin, UserRole::SupplyManager])
            || $user->hasPermissionTo('run_calculations');
    }

    private function canApprove(User $user): bool
    {
        return $user->hasRole(UserRole::Admin)
            || $user->hasPermissionTo('approve_order_proposals')
            || $user->hasPermissionTo('manage_settings');
    }
}
