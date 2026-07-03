<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\IncidentSlaPolicy;
use App\Models\User;

class IncidentSlaPolicyPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canManage($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, IncidentSlaPolicy $policy): bool
    {
        return $this->canManage($user);
    }

    public function delete(User $user, IncidentSlaPolicy $policy): bool
    {
        return $this->canManage($user);
    }

    private function canManage(User $user): bool
    {
        return $user->hasRole(UserRole::Admin) || $user->hasPermissionTo('manage_settings');
    }
}
