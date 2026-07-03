<?php

namespace App\Policies;

use App\Models\PilotRun;
use App\Models\PilotSupplier;
use App\Models\User;

class PilotRunPolicy
{
    public function view(User $user, PilotRun $pilotRun): bool
    {
        return $user->can('view', $pilotRun->pilotSupplier);
    }

    public function run(User $user, PilotSupplier $pilotSupplier): bool
    {
        return $user->can('runChecks', $pilotSupplier);
    }
}
