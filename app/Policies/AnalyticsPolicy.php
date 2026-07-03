<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class AnalyticsPolicy
{
    public function view(User $user): bool
    {
        return $user->hasAnyRole([UserRole::Admin, UserRole::SupplyManager, UserRole::LogisticsManager, UserRole::Accountant])
            || $user->hasPermissionTo('view_analytics');
    }

    public function export(User $user): bool
    {
        return $user->hasAnyRole([UserRole::Admin, UserRole::SupplyManager, UserRole::LogisticsManager])
            || $user->hasPermissionTo('export_analytics');
    }

    public function viewSensitiveAudit(User $user): bool
    {
        return $user->hasRole(UserRole::Admin) || $user->hasPermissionTo('view_audit_logs');
    }
}
