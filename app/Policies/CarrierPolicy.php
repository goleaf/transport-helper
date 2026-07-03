<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Carrier;
use App\Models\User;

class CarrierPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->viewTransport($user);
    }

    public function view(User $user, Carrier $carrier): bool
    {
        return $this->viewTransport($user);
    }

    public function create(User $user): bool
    {
        return $this->manageTransport($user);
    }

    public function update(User $user, Carrier $carrier): bool
    {
        return $this->manageTransport($user);
    }

    private function viewTransport(User $user): bool
    {
        return $user->hasAnyRole([
            UserRole::Admin,
            UserRole::SupplyManager,
            UserRole::LogisticsManager,
            UserRole::Accountant,
            UserRole::Viewer,
        ]) || $user->hasPermissionTo('view_logistics') || $user->hasPermissionTo('manage_transport');
    }

    private function manageTransport(User $user): bool
    {
        return $user->hasAnyRole([UserRole::Admin, UserRole::LogisticsManager])
            || $user->hasPermissionTo('manage_transport');
    }
}
