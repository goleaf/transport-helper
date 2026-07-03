<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\CalculationScenario;
use App\Models\User;

class CalculationScenarioPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canView($user);
    }

    public function view(User $user, CalculationScenario $scenario): bool
    {
        return $this->canView($user);
    }

    public function simulate(User $user): bool
    {
        return $this->canRun($user);
    }

    public function compare(User $user): bool
    {
        return $this->canView($user);
    }

    public function export(User $user, CalculationScenario $scenario): bool
    {
        return $this->canView($user) || $user->hasPermissionTo('export_analytics');
    }

    public function createProposal(User $user, CalculationScenario $scenario): bool
    {
        return false;
    }

    private function canView(User $user): bool
    {
        return $user->hasAnyRole([UserRole::Admin, UserRole::SupplyManager, UserRole::Viewer])
            || $user->hasPermissionTo('view_calculations')
            || $user->hasPermissionTo('view_analytics');
    }

    private function canRun(User $user): bool
    {
        return $user->hasAnyRole([UserRole::Admin, UserRole::SupplyManager])
            || $user->hasPermissionTo('run_calculations');
    }
}
