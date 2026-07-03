<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\SupplierProductPrice;
use App\Models\User;

class SupplierProductPricePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canView($user);
    }

    public function view(User $user, SupplierProductPrice $price): bool
    {
        return $this->canView($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, SupplierProductPrice $price): bool
    {
        return $this->canManage($user);
    }

    private function canView(User $user): bool
    {
        return $user->hasAnyRole([UserRole::Admin, UserRole::SupplyManager, UserRole::Accountant])
            || $user->hasPermissionTo('view_analytics')
            || $user->hasPermissionTo('view_calculations');
    }

    private function canManage(User $user): bool
    {
        return $user->hasAnyRole([UserRole::Admin, UserRole::SupplyManager])
            || $user->hasPermissionTo('manage_settings');
    }
}
