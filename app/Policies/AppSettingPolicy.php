<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\AppSetting;
use App\Models\User;

class AppSettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->viewSettings($user);
    }

    public function view(User $user, AppSetting $appSetting): bool
    {
        return $this->viewSettings($user);
    }

    public function create(User $user): bool
    {
        return $this->manageSettings($user);
    }

    public function update(User $user, AppSetting $appSetting): bool
    {
        return $this->manageSettings($user);
    }

    public function delete(User $user, AppSetting $appSetting): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    public function restore(User $user, AppSetting $appSetting): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    public function forceDelete(User $user, AppSetting $appSetting): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    private function manageSettings(User $user): bool
    {
        return $user->hasRole(UserRole::Admin)
            || $user->hasPermissionTo('manage_settings');
    }

    private function viewSettings(User $user): bool
    {
        return $this->manageSettings($user)
            || $user->hasRole(UserRole::SupplyManager);
    }
}
