<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\OrderProposal;
use App\Models\User;

class OrderProposalPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, OrderProposal $orderProposal): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $this->manage($user);
    }

    public function update(User $user, OrderProposal $orderProposal): bool
    {
        return $this->manage($user);
    }

    public function approve(User $user, OrderProposal $orderProposal): bool
    {
        return $user->hasAnyRole([UserRole::Admin, UserRole::SupplyManager])
            || $user->hasPermissionTo('approve_order_proposals');
    }

    public function convertToSupplierOrder(User $user, OrderProposal $orderProposal): bool
    {
        return $user->hasAnyRole([UserRole::Admin, UserRole::SupplyManager])
            || $user->hasPermissionTo('create_supplier_orders');
    }

    public function delete(User $user, OrderProposal $orderProposal): bool
    {
        return false;
    }

    public function restore(User $user, OrderProposal $orderProposal): bool
    {
        return false;
    }

    public function forceDelete(User $user, OrderProposal $orderProposal): bool
    {
        return false;
    }

    private function manage(User $user): bool
    {
        return $user->hasAnyRole([UserRole::Admin, UserRole::SupplyManager]);
    }
}
