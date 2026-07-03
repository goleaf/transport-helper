<?php

namespace App\Policies;

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
        return $user->canManageSupplyWorkflow();
    }

    public function update(User $user, EmailMessage $emailMessage): bool
    {
        return $user->canManageSupplyWorkflow();
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
}
