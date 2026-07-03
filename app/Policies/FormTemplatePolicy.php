<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\FormTemplate;
use App\Models\User;

class FormTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageSupplyWorkflow()
            || $user->hasAnyRole([UserRole::LogisticsManager, UserRole::Accountant, UserRole::Viewer]);
    }

    public function view(User $user, FormTemplate $formTemplate): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->canManageSupplyWorkflow()
            || $user->hasPermissionTo('manage_settings');
    }

    public function update(User $user, FormTemplate $formTemplate): bool
    {
        return $this->create($user);
    }

    public function addField(User $user, FormTemplate $formTemplate): bool
    {
        return $this->update($user, $formTemplate);
    }
}
