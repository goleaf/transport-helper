<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\ReportRun;
use App\Models\User;

class ReportRunPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canViewAnalytics($user);
    }

    public function view(User $user, ReportRun $reportRun): bool
    {
        return $this->canViewAnalytics($user)
            && ($reportRun->started_by_user_id === null || $reportRun->started_by_user_id === $user->id || $user->hasRole(UserRole::Admin));
    }

    public function create(User $user): bool
    {
        return $this->canViewAnalytics($user);
    }

    public function update(User $user, ReportRun $reportRun): bool
    {
        return false;
    }

    public function delete(User $user, ReportRun $reportRun): bool
    {
        return false;
    }

    private function canViewAnalytics(User $user): bool
    {
        return $user->hasAnyRole([UserRole::Admin, UserRole::SupplyManager, UserRole::LogisticsManager, UserRole::Accountant])
            || $user->hasPermissionTo('view_analytics');
    }
}
