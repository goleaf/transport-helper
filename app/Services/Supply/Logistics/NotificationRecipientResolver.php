<?php

namespace App\Services\Supply\Logistics;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Collection;

class NotificationRecipientResolver
{
    /**
     * @param  array<string, mixed>  $context
     * @return Collection<int, User>
     */
    public function resolve(string $notificationType, array $context = []): Collection
    {
        if (($context['user'] ?? null) instanceof User) {
            return collect([$context['user']]);
        }

        $roles = match ($notificationType) {
            'health_check_warning' => [UserRole::Admin->value],
            'supplier_confirmation_received', 'supplier_confirmation_needs_review', 'quantity_mismatch' => [
                UserRole::Admin->value,
                UserRole::SupplyManager->value,
            ],
            default => [
                UserRole::Admin->value,
                UserRole::SupplyManager->value,
                UserRole::LogisticsManager->value,
            ],
        };

        $users = User::query()
            ->select(['id', 'name', 'email', 'password', 'role'])
            ->whereIn('role', $roles)
            ->limit(100)
            ->get();

        if ($users->isNotEmpty()) {
            return $users;
        }

        return User::query()
            ->select(['id', 'name', 'email', 'password', 'role'])
            ->orderBy('id')
            ->limit(1)
            ->get();
    }
}
