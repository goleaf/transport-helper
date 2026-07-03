<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\ProcurementBudget;
use App\Models\User;

class ProcurementBudgetPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canView($user);
    }

    public function view(User $user, ProcurementBudget $budget): bool
    {
        return $this->canView($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, ProcurementBudget $budget): bool
    {
        return $this->canManage($user);
    }

    private function canView(User $user): bool
    {
        return $user->hasAnyRole([UserRole::Admin, UserRole::SupplyManager, UserRole::Accountant])
            || $user->hasPermissionTo('view_analytics')
            || $user->hasPermissionTo('manage_settings');
    }

    private function canManage(User $user): bool
    {
        return $user->hasRole(UserRole::Admin) || $user->hasPermissionTo('manage_settings');
    }
}
