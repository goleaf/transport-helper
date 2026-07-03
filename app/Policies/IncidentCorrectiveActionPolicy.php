<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\IncidentCorrectiveAction;
use App\Models\OperationalIncident;
use App\Models\User;

class IncidentCorrectiveActionPolicy
{
    public function create(User $user, ?OperationalIncident $incident = null): bool
    {
        return $this->canManage($user) || ($incident !== null && $incident->assigned_user_id === $user->id);
    }

    public function update(User $user, IncidentCorrectiveAction $action): bool
    {
        return $this->canManage($user) || $action->owner_user_id === $user->id || $action->incident?->assigned_user_id === $user->id;
    }

    public function markDone(User $user, IncidentCorrectiveAction $action): bool
    {
        return $this->update($user, $action);
    }

    public function verify(User $user, IncidentCorrectiveAction $action): bool
    {
        return $this->canManage($user);
    }

    public function cancel(User $user, IncidentCorrectiveAction $action): bool
    {
        return $this->update($user, $action);
    }

    private function canManage(User $user): bool
    {
        return $user->hasAnyRole([UserRole::Admin, UserRole::SupplyManager, UserRole::LogisticsManager])
            || $user->hasPermissionTo('manage_settings')
            || $user->hasPermissionTo('manage_logistics');
    }
}
