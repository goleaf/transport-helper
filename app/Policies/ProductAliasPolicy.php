<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\ProductAlias;
use App\Models\User;

class ProductAliasPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canView($user);
    }

    public function view(User $user, ProductAlias $alias): bool
    {
        return $this->canView($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, ProductAlias $alias): bool
    {
        return $this->canManage($user);
    }

    private function canView(User $user): bool
    {
        return $user->hasAnyRole([UserRole::Admin, UserRole::SupplyManager, UserRole::Viewer])
            || $user->hasPermissionTo('view_products');
    }

    private function canManage(User $user): bool
    {
        return $user->hasRole(UserRole::Admin) || $user->hasPermissionTo('manage_products');
    }
}
