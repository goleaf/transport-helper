<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\SupplierConfirmation;
use App\Models\User;

class SupplierConfirmationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            UserRole::Admin,
            UserRole::SupplyManager,
            UserRole::LogisticsManager,
            UserRole::Accountant,
            UserRole::Viewer,
        ]) || $user->hasPermissionTo('view_supplier_confirmations');
    }

    public function view(User $user, SupplierConfirmation $supplierConfirmation): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->apply($user);
    }

    public function createManual(User $user): bool
    {
        return $this->apply($user);
    }

    public function applyFromAiExtraction(User $user): bool
    {
        return $this->apply($user);
    }

    public function applyFromFormAutofill(User $user): bool
    {
        return $this->apply($user);
    }

    public function resolveReview(User $user, ?SupplierConfirmation $supplierConfirmation = null): bool
    {
        return $this->apply($user);
    }

    public function update(User $user, SupplierConfirmation $supplierConfirmation): bool
    {
        return $this->apply($user);
    }

    public function apply(User $user, ?SupplierConfirmation $supplierConfirmation = null): bool
    {
        return $user->hasAnyRole([UserRole::Admin, UserRole::SupplyManager])
            || $user->hasPermissionTo('apply_supplier_confirmations');
    }

    public function delete(User $user, SupplierConfirmation $supplierConfirmation): bool
    {
        return false;
    }

    public function restore(User $user, SupplierConfirmation $supplierConfirmation): bool
    {
        return false;
    }

    public function forceDelete(User $user, SupplierConfirmation $supplierConfirmation): bool
    {
        return false;
    }
}
