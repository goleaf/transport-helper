<?php

namespace App\Policies;

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
        return $user->canManageSupplyWorkflow();
    }

    public function update(User $user, OrderProposal $orderProposal): bool
    {
        return $user->canManageSupplyWorkflow();
    }

    public function approve(User $user, OrderProposal $orderProposal): bool
    {
        return $user->canManageSupplyWorkflow();
    }

    public function convertToSupplierOrder(User $user, OrderProposal $orderProposal): bool
    {
        return $user->canManageSupplyWorkflow();
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
}
