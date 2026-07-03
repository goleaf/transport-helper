<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\OperationalIncident;
use App\Models\User;

class OperationalIncidentPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canView($user);
    }

    public function view(User $user, OperationalIncident $incident): bool
    {
        return $this->canView($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, OperationalIncident $incident): bool
    {
        return $this->canManage($user) || $incident->assigned_user_id === $user->id;
    }

    public function assign(User $user, OperationalIncident $incident): bool
    {
        return $this->canManage($user);
    }

    public function changeStatus(User $user, OperationalIncident $incident): bool
    {
        return $this->update($user, $incident);
    }

    public function resolve(User $user, OperationalIncident $incident): bool
    {
        return $this->update($user, $incident);
    }

    public function close(User $user, OperationalIncident $incident): bool
    {
        return $user->hasRole(UserRole::Admin)
            || $user->hasPermissionTo('manage_settings')
            || $incident->assigned_user_id === $user->id;
    }

    public function comment(User $user, OperationalIncident $incident): bool
    {
        return $this->canView($user);
    }

    public function export(User $user): bool
    {
        return $user->hasRole(UserRole::Admin)
            || $user->hasAnyRole([UserRole::SupplyManager, UserRole::LogisticsManager])
            || $user->hasPermissionTo('view_audit_logs');
    }

    public function runDetection(User $user): bool
    {
        return $this->canManage($user);
    }

    private function canView(User $user): bool
    {
        return $user->hasAnyRole([
            UserRole::Admin,
            UserRole::SupplyManager,
            UserRole::LogisticsManager,
            UserRole::Accountant,
        ]) || $user->hasPermissionTo('view_audit_logs');
    }

    private function canManage(User $user): bool
    {
        return $user->hasAnyRole([UserRole::Admin, UserRole::SupplyManager, UserRole::LogisticsManager])
            || $user->hasPermissionTo('manage_logistics')
            || $user->hasPermissionTo('manage_settings');
    }
}
