<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\DataStewardAssignment;
use App\Models\User;

class DataStewardAssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canView($user);
    }

    public function view(User $user, DataStewardAssignment $assignment): bool
    {
        return $this->canView($user);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::Admin) || $user->hasPermissionTo('manage_products') || $user->hasPermissionTo('manage_settings');
    }

    public function update(User $user, DataStewardAssignment $assignment): bool
    {
        return $this->create($user);
    }

    private function canView(User $user): bool
    {
        return $user->hasAnyRole([UserRole::Admin, UserRole::SupplyManager, UserRole::Viewer])
            || $user->hasPermissionTo('view_products');
    }
}
