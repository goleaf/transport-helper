<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\OrderProposalItem;
use App\Models\User;

class OrderProposalItemPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, OrderProposalItem $orderProposalItem): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $this->manage($user);
    }

    public function update(User $user, OrderProposalItem $orderProposalItem): bool
    {
        return $this->manage($user);
    }

    public function approve(User $user, OrderProposalItem $orderProposalItem): bool
    {
        return $user->hasAnyRole([UserRole::Admin, UserRole::SupplyManager])
            || $user->hasPermissionTo('approve_order_proposals');
    }

    public function adjust(User $user, OrderProposalItem $orderProposalItem): bool
    {
        return $user->hasAnyRole([UserRole::Admin, UserRole::SupplyManager])
            || $user->hasPermissionTo('adjust_order_quantities');
    }

    public function reject(User $user, OrderProposalItem $orderProposalItem): bool
    {
        return $this->manage($user);
    }

    public function delete(User $user, OrderProposalItem $orderProposalItem): bool
    {
        return false;
    }

    public function restore(User $user, OrderProposalItem $orderProposalItem): bool
    {
        return false;
    }

    public function forceDelete(User $user, OrderProposalItem $orderProposalItem): bool
    {
        return false;
    }

    private function manage(User $user): bool
    {
        return $user->hasAnyRole([UserRole::Admin, UserRole::SupplyManager]);
    }
}
