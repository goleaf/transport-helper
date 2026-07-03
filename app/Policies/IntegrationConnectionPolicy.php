<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\IntegrationConnection;
use App\Models\User;

class IntegrationConnectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->manage($user);
    }

    public function view(User $user, IntegrationConnection $integrationConnection): bool
    {
        return $this->manage($user);
    }

    public function create(User $user): bool
    {
        return $this->manage($user);
    }

    public function update(User $user, IntegrationConnection $integrationConnection): bool
    {
        return $this->manage($user);
    }

    public function manage(User $user, ?IntegrationConnection $integrationConnection = null): bool
    {
        return $user->hasRole(UserRole::Admin)
            || $user->hasPermissionTo('manage_integrations');
    }

    public function delete(User $user, IntegrationConnection $integrationConnection): bool
    {
        return $this->manage($user);
    }

    public function restore(User $user, IntegrationConnection $integrationConnection): bool
    {
        return $this->manage($user);
    }

    public function forceDelete(User $user, IntegrationConnection $integrationConnection): bool
    {
        return $user->hasRole(UserRole::Admin);
    }
}
