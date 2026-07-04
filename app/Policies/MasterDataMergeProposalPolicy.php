<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\MasterDataMergeProposal;
use App\Models\User;

class MasterDataMergeProposalPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canView($user);
    }

    public function view(User $user, MasterDataMergeProposal $proposal): bool
    {
        return $this->canView($user);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::Admin) || $user->hasPermissionTo('manage_products');
    }

    public function approve(User $user, MasterDataMergeProposal $proposal): bool
    {
        return $user->hasRole(UserRole::Admin) || $user->hasPermissionTo('manage_settings');
    }

    public function execute(User $user, MasterDataMergeProposal $proposal): bool
    {
        return $user->hasRole(UserRole::Admin) || $user->hasPermissionTo('manage_settings');
    }

    private function canView(User $user): bool
    {
        return $user->hasAnyRole([UserRole::Admin, UserRole::SupplyManager, UserRole::Viewer])
            || $user->hasPermissionTo('view_products');
    }
}
