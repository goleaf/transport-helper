<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\UnknownSkuResolution;
use App\Models\User;

class UnknownSkuResolutionPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canView($user);
    }

    public function view(User $user, UnknownSkuResolution $resolution): bool
    {
        return $this->canView($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([UserRole::Admin, UserRole::SupplyManager])
            || $user->hasPermissionTo('import_data')
            || $user->hasPermissionTo('review_ai_extractions')
            || $user->hasPermissionTo('apply_supplier_confirmations');
    }

    public function update(User $user, UnknownSkuResolution $resolution): bool
    {
        return $user->hasRole(UserRole::Admin) || $user->hasPermissionTo('manage_products');
    }

    private function canView(User $user): bool
    {
        return $user->hasAnyRole([UserRole::Admin, UserRole::SupplyManager, UserRole::Viewer])
            || $user->hasPermissionTo('view_products')
            || $user->hasPermissionTo('review_ai_extractions');
    }
}
