<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\EmailMessage;
use App\Models\User;

class EmailMessagePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, EmailMessage $emailMessage): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $this->manage($user);
    }

    public function update(User $user, EmailMessage $emailMessage): bool
    {
        return $this->manage($user);
    }

    public function delete(User $user, EmailMessage $emailMessage): bool
    {
        return false;
    }

    public function restore(User $user, EmailMessage $emailMessage): bool
    {
        return false;
    }

    public function forceDelete(User $user, EmailMessage $emailMessage): bool
    {
        return false;
    }

    private function manage(User $user): bool
    {
        return $user->hasAnyRole([UserRole::Admin, UserRole::SupplyManager]);
    }
}
