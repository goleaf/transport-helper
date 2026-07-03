<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\ImportBatch;
use App\Models\User;

class ImportBatchPolicy
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

    public function view(User $user, ImportBatch $importBatch): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([UserRole::Admin, UserRole::SupplyManager])
            || $user->hasPermissionTo('import_data');
    }

    public function update(User $user, ImportBatch $importBatch): bool
    {
        return $this->create($user);
    }

    public function rollback(User $user, ImportBatch $importBatch): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    public function delete(User $user, ImportBatch $importBatch): bool
    {
        return false;
    }

    public function restore(User $user, ImportBatch $importBatch): bool
    {
        return false;
    }

    public function forceDelete(User $user, ImportBatch $importBatch): bool
    {
        return false;
    }
}
