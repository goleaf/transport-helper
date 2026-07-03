<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\LogisticsRecord;
use App\Models\User;

class LogisticsRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canView($user);
    }

    public function view(User $user, LogisticsRecord $logisticsRecord): bool
    {
        return $this->canView($user);
    }

    public function update(User $user, LogisticsRecord $logisticsRecord): bool
    {
        return $this->canManage($user);
    }

    public function updateStatus(User $user, LogisticsRecord $logisticsRecord): bool
    {
        return $this->canManage($user);
    }

    public function recordReceipt(User $user, LogisticsRecord $logisticsRecord): bool
    {
        return $this->canManage($user);
    }

    public function export(User $user): bool
    {
        return $this->canView($user) || $this->canManage($user);
    }

    public function sync(User $user): bool
    {
        return $this->canManage($user) || $user->hasPermissionTo('manage_integrations');
    }

    public function syncGoogleSheets(User $user): bool
    {
        return $this->sync($user);
    }

    private function canView(User $user): bool
    {
        return $user->hasAnyRole([
            UserRole::Admin,
            UserRole::SupplyManager,
            UserRole::LogisticsManager,
            UserRole::Accountant,
            UserRole::Viewer,
        ]) || $user->hasPermissionTo('view_logistics');
    }

    private function canManage(User $user): bool
    {
        return $user->canManageLogisticsWorkflow() || $user->hasPermissionTo('manage_logistics');
    }
}
