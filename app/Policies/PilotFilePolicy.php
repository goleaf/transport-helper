<?php

namespace App\Policies;

use App\Models\PilotFile;
use App\Models\PilotSupplier;
use App\Models\User;

class PilotFilePolicy
{
    public function view(User $user, PilotFile $pilotFile): bool
    {
        return $user->can('view', $pilotFile->pilotSupplier);
    }

    public function upload(User $user, PilotSupplier $pilotSupplier): bool
    {
        return $user->can('uploadFile', $pilotSupplier);
    }

    public function delete(User $user, PilotFile $pilotFile): bool
    {
        return $user->can('uploadFile', $pilotFile->pilotSupplier);
    }
}
