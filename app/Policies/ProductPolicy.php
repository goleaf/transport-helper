<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            UserRole::Admin,
            UserRole::SupplyManager,
            UserRole::LogisticsManager,
            UserRole::Accountant,
            UserRole::Viewer,
        ]) || $user->hasPermissionTo('view_products');
    }

    public function view(User $user, Product $product): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->manage($user);
    }

    public function update(User $user, Product $product): bool
    {
        return $this->manage($user);
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    public function restore(User $user, Product $product): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    public function forceDelete(User $user, Product $product): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    private function manage(User $user): bool
    {
        return $user->hasAnyRole([UserRole::Admin, UserRole::SupplyManager])
            || $user->hasPermissionTo('manage_products');
    }
}
