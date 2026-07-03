<?php

namespace App\Policies;

use App\Models\SupplyOrder;
use App\Models\User;

class SupplyOrderPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, SupplyOrder $supplyOrder): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->canManageSupplyWorkflow();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, SupplyOrder $supplyOrder): bool
    {
        return $user->canManageSupplyWorkflow();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SupplyOrder $supplyOrder): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, SupplyOrder $supplyOrder): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, SupplyOrder $supplyOrder): bool
    {
        return false;
    }

    public function submitToManufacturer(User $user, SupplyOrder $supplyOrder): bool
    {
        return $user->canManageSupplyWorkflow();
    }

    public function updateLogistics(User $user, SupplyOrder $supplyOrder): bool
    {
        return $user->canManageLogisticsWorkflow();
    }
}
