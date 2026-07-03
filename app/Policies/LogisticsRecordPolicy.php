<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\LogisticsRecord;
use App\Models\User;

class LogisticsRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            UserRole::Admin,
            UserRole::SupplyManager,
            UserRole::LogisticsManager,
            UserRole::Accountant,
            UserRole::Viewer,
        ]) || $user->hasPermissionTo('view_logistics');
    }

    public function view(User $user, LogisticsRecord $logisticsRecord): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->canManageLogisticsWorkflow();
    }

    public function update(User $user, LogisticsRecord $logisticsRecord): bool
    {
        return $user->canManageLogisticsWorkflow();
    }

    public function export(User $user): bool
    {
        return $user->canManageLogisticsWorkflow();
    }

    public function syncGoogleSheets(User $user): bool
    {
        return $user->canManageLogisticsWorkflow();
    }

    public function delete(User $user, LogisticsRecord $logisticsRecord): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    public function restore(User $user, LogisticsRecord $logisticsRecord): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    public function forceDelete(User $user, LogisticsRecord $logisticsRecord): bool
    {
        return $user->hasRole(UserRole::Admin);
    }
}
