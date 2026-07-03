<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\CarrierQuote;
use App\Models\User;

class CarrierQuotePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            UserRole::Admin,
            UserRole::SupplyManager,
            UserRole::LogisticsManager,
            UserRole::Accountant,
            UserRole::Viewer,
        ]);
    }

    public function view(User $user, CarrierQuote $carrierQuote): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->manage($user);
    }

    public function applyFromAi(User $user, CarrierQuote $carrierQuote): bool
    {
        return $this->manage($user);
    }

    public function applyFromFormAutofill(User $user, CarrierQuote $carrierQuote): bool
    {
        return $this->manage($user);
    }

    public function score(User $user, CarrierQuote $carrierQuote): bool
    {
        return $this->manage($user) || $this->select($user, $carrierQuote);
    }

    public function update(User $user, CarrierQuote $carrierQuote): bool
    {
        return $this->manage($user);
    }

    public function select(User $user, CarrierQuote $carrierQuote): bool
    {
        return $user->hasAnyRole([UserRole::Admin, UserRole::LogisticsManager])
            || $user->hasPermissionTo('select_carrier');
    }

    public function reject(User $user, CarrierQuote $carrierQuote): bool
    {
        return $this->manage($user);
    }

    public function delete(User $user, CarrierQuote $carrierQuote): bool
    {
        return false;
    }

    public function restore(User $user, CarrierQuote $carrierQuote): bool
    {
        return false;
    }

    public function forceDelete(User $user, CarrierQuote $carrierQuote): bool
    {
        return false;
    }

    private function manage(User $user): bool
    {
        return $user->hasAnyRole([UserRole::Admin, UserRole::LogisticsManager])
            || $user->hasPermissionTo('manage_transport');
    }
}
