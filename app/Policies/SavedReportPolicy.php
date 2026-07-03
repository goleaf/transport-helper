<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\SavedReport;
use App\Models\User;

class SavedReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canViewAnalytics($user);
    }

    public function view(User $user, SavedReport $savedReport): bool
    {
        return $this->canViewAnalytics($user)
            && ($savedReport->is_shared || $savedReport->user_id === $user->id || $savedReport->created_by_user_id === $user->id || $this->canManage($user));
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, SavedReport $savedReport): bool
    {
        return $this->canManage($user) || $savedReport->user_id === $user->id || $savedReport->created_by_user_id === $user->id;
    }

    public function delete(User $user, SavedReport $savedReport): bool
    {
        return $this->update($user, $savedReport);
    }

    public function setDefault(User $user, SavedReport $savedReport): bool
    {
        return $this->update($user, $savedReport);
    }

    private function canViewAnalytics(User $user): bool
    {
        return $user->hasAnyRole([UserRole::Admin, UserRole::SupplyManager, UserRole::LogisticsManager, UserRole::Accountant])
            || $user->hasPermissionTo('view_analytics');
    }

    private function canManage(User $user): bool
    {
        return $user->hasRole(UserRole::Admin) || $user->hasPermissionTo('manage_saved_reports');
    }
}
