<?php

namespace App\Policies;

use App\Models\SupplierOrder;
use App\Models\User;

class SupplierOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, SupplierOrder $supplierOrder): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->canManageSupplyWorkflow();
    }

    public function update(User $user, SupplierOrder $supplierOrder): bool
    {
        return $user->canManageSupplyWorkflow();
    }

    public function export(User $user, SupplierOrder $supplierOrder): bool
    {
        return $user->canManageSupplyWorkflow();
    }

    public function prepareEmail(User $user, SupplierOrder $supplierOrder): bool
    {
        return $user->canManageSupplyWorkflow();
    }

    public function approveEmail(User $user, SupplierOrder $supplierOrder): bool
    {
        return $user->canManageSupplyWorkflow();
    }

    public function sendEmail(User $user, SupplierOrder $supplierOrder): bool
    {
        return $user->canManageSupplyWorkflow();
    }

    public function delete(User $user, SupplierOrder $supplierOrder): bool
    {
        return false;
    }

    public function restore(User $user, SupplierOrder $supplierOrder): bool
    {
        return false;
    }

    public function forceDelete(User $user, SupplierOrder $supplierOrder): bool
    {
        return false;
    }
}
