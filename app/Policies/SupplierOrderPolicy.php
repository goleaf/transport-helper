<?php

namespace App\Policies;

use App\Enums\UserRole;
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
        return $this->manage($user);
    }

    public function update(User $user, SupplierOrder $supplierOrder): bool
    {
        return $this->manage($user);
    }

    public function approve(User $user, SupplierOrder $supplierOrder): bool
    {
        return $this->manage($user);
    }

    public function export(User $user, SupplierOrder $supplierOrder): bool
    {
        return $this->manage($user);
    }

    public function prepareEmail(User $user, SupplierOrder $supplierOrder): bool
    {
        return $this->manage($user);
    }

    public function approveEmail(User $user, SupplierOrder $supplierOrder): bool
    {
        return $user->hasAnyRole([UserRole::Admin, UserRole::SupplyManager])
            || $user->hasPermissionTo('approve_supplier_emails');
    }

    public function sendEmail(User $user, SupplierOrder $supplierOrder): bool
    {
        return $user->hasAnyRole([UserRole::Admin, UserRole::SupplyManager])
            || $user->hasPermissionTo('send_supplier_emails');
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

    private function manage(User $user): bool
    {
        return $user->hasAnyRole([UserRole::Admin, UserRole::SupplyManager]);
    }
}
