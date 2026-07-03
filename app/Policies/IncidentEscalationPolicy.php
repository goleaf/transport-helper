<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\IncidentEscalation;
use App\Models\OperationalIncident;
use App\Models\User;

class IncidentEscalationPolicy
{
    public function view(User $user, IncidentEscalation $escalation): bool
    {
        return $this->canManage($user) || $escalation->escalated_to_user_id === $user->id;
    }

    public function create(User $user, ?OperationalIncident $incident = null): bool
    {
        return $this->canManage($user);
    }

    public function resolve(User $user, IncidentEscalation $escalation): bool
    {
        return $this->canManage($user) || $escalation->escalated_to_user_id === $user->id;
    }

    private function canManage(User $user): bool
    {
        return $user->hasAnyRole([UserRole::Admin, UserRole::SupplyManager, UserRole::LogisticsManager])
            || $user->hasPermissionTo('manage_settings')
            || $user->hasPermissionTo('manage_logistics');
    }
}
