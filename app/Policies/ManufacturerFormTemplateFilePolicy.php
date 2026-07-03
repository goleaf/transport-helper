<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\ManufacturerFormTemplateFile;
use App\Models\User;

class ManufacturerFormTemplateFilePolicy
{
    public function view(User $user, ManufacturerFormTemplateFile $manufacturerFormTemplateFile): bool
    {
        return $this->manageForms($user);
    }

    public function upload(User $user): bool
    {
        return $this->manageForms($user);
    }

    public function updateMapping(User $user): bool
    {
        return $this->manageForms($user);
    }

    public function preview(User $user): bool
    {
        return $this->manageForms($user);
    }

    public function export(User $user): bool
    {
        return $this->manageForms($user);
    }

    private function manageForms(User $user): bool
    {
        return $user->hasRole(UserRole::Admin)
            || $user->hasPermissionTo('manage_settings')
            || $user->hasPermissionTo('create_supplier_orders');
    }
}
