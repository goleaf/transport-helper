<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\MasterDataChangeRequest;
use App\Models\User;

class MasterDataChangeRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canView($user);
    }

    public function view(User $user, MasterDataChangeRequest $request): bool
    {
        return $this->canView($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([UserRole::Admin, UserRole::SupplyManager])
            || $user->hasPermissionTo('manage_products')
            || $user->hasPermissionTo('import_data')
            || $user->hasPermissionTo('review_ai_extractions');
    }

    public function approve(User $user, MasterDataChangeRequest $request): bool
    {
        return $this->canApprove($user);
    }

    public function apply(User $user, MasterDataChangeRequest $request): bool
    {
        return $this->canApprove($user);
    }

    private function canView(User $user): bool
    {
        return $user->hasAnyRole([UserRole::Admin, UserRole::SupplyManager, UserRole::Viewer])
            || $user->hasPermissionTo('view_products');
    }

    private function canApprove(User $user): bool
    {
        return $user->hasRole(UserRole::Admin) || $user->hasPermissionTo('manage_products') || $user->hasPermissionTo('manage_settings');
    }
}
