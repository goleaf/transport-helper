<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\PilotSupplier;
use App\Models\User;

class PilotSupplierPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canView($user);
    }

    public function view(User $user, PilotSupplier $pilotSupplier): bool
    {
        return $this->canView($user);
    }

    public function create(User $user): bool
    {
        return $this->canConfigure($user);
    }

    public function update(User $user, PilotSupplier $pilotSupplier): bool
    {
        return $this->canConfigure($user);
    }

    public function uploadFile(User $user, PilotSupplier $pilotSupplier): bool
    {
        return $this->canOperate($user);
    }

    public function runChecks(User $user, PilotSupplier $pilotSupplier): bool
    {
        return $this->canOperate($user) || $this->canManageLogistics($user);
    }

    public function updateUat(User $user, PilotSupplier $pilotSupplier): bool
    {
        return $this->canOperate($user) || $this->canManageLogistics($user);
    }

    public function approveForUat(User $user, PilotSupplier $pilotSupplier): bool
    {
        return $this->canApprove($user);
    }

    public function approveForLive(User $user, PilotSupplier $pilotSupplier): bool
    {
        return $this->canApprove($user);
    }

    public function block(User $user, PilotSupplier $pilotSupplier): bool
    {
        return $this->canApprove($user);
    }

    public function archive(User $user, PilotSupplier $pilotSupplier): bool
    {
        return $this->canConfigure($user);
    }

    private function canView(User $user): bool
    {
        return $user->hasAnyRole([
            UserRole::Admin,
            UserRole::SupplyManager,
            UserRole::LogisticsManager,
            UserRole::Accountant,
            UserRole::Viewer,
        ]);
    }

    private function canConfigure(User $user): bool
    {
        return $user->hasAnyRole([UserRole::Admin, UserRole::SupplyManager])
            || $user->hasPermissionTo('manage_settings')
            || $user->hasPermissionTo('manage_integrations');
    }

    private function canOperate(User $user): bool
    {
        return $this->canConfigure($user)
            || $user->hasPermissionTo('import_data')
            || $user->hasPermissionTo('run_calculations');
    }

    private function canManageLogistics(User $user): bool
    {
        return $user->hasAnyRole([UserRole::Admin, UserRole::LogisticsManager])
            || $user->hasPermissionTo('manage_logistics');
    }

    private function canApprove(User $user): bool
    {
        return $user->hasRole(UserRole::Admin)
            || $user->hasPermissionTo('manage_settings')
            || $user->hasPermissionTo('manage_integrations');
    }
}
