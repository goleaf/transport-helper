<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\ReplenishmentProfile;
use App\Models\User;

class ReplenishmentProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canView($user);
    }

    public function view(User $user, ReplenishmentProfile $profile): bool
    {
        return $this->canView($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, ReplenishmentProfile $profile): bool
    {
        return $this->canManage($user);
    }

    public function archive(User $user, ReplenishmentProfile $profile): bool
    {
        return $this->canManage($user);
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
            || $user->hasPermissionTo('manage_settings')
            || $user->hasPermissionTo('run_calculations');
    }
}
