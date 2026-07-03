<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;

class NotificationPolicy
{
    public function view(User $user, DatabaseNotification $notification): bool
    {
        return $notification->notifiable_id === $user->id || $user->hasRole(UserRole::Admin);
    }

    public function markAsRead(User $user, DatabaseNotification $notification): bool
    {
        return $this->view($user, $notification);
    }
}
