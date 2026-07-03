<?php

namespace App\Policies;

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
        return $user->canManageSupplyWorkflow();
    }

    public function update(User $user, OrderProposalItem $orderProposalItem): bool
    {
        return $user->canManageSupplyWorkflow();
    }

    public function approve(User $user, OrderProposalItem $orderProposalItem): bool
    {
        return $user->canManageSupplyWorkflow();
    }

    public function adjust(User $user, OrderProposalItem $orderProposalItem): bool
    {
        return $user->canManageSupplyWorkflow();
    }

    public function reject(User $user, OrderProposalItem $orderProposalItem): bool
    {
        return $user->canManageSupplyWorkflow();
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
}
