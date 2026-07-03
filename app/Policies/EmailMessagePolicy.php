<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\EmailMessage;
use App\Models\SupplierOrder;
use App\Models\User;

class EmailMessagePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'review_ai_extractions')
            || $this->hasPermission($user, 'approve_supplier_emails')
            || $this->hasPermission($user, 'send_supplier_emails')
            || $this->hasAnyRole($user, [
                UserRole::Admin,
                UserRole::SupplyManager,
                UserRole::LogisticsManager,
                UserRole::Accountant,
                UserRole::Viewer,
            ]);
    }

    public function view(User $user, EmailMessage $emailMessage): bool
    {
        $order = $this->relatedOrder($emailMessage);

        return $order instanceof SupplierOrder
            ? $user->can('view', $order)
            : $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->manage($user);
    }

    public function createManual(User $user): bool
    {
        return $this->manage($user)
            || $this->hasPermission($user, 'review_ai_extractions')
            || $this->hasPermission($user, 'approve_supplier_emails');
    }

    public function analyze(User $user, EmailMessage $emailMessage): bool
    {
        return $this->hasPermission($user, 'review_ai_extractions') || $this->manage($user);
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
        return $this->hasAnyRole($user, [UserRole::Admin, UserRole::SupplyManager]);
    }

    public function approve(User $user, EmailMessage $emailMessage): bool
    {
        return $this->hasPermission($user, 'approve_supplier_emails') || $this->manage($user);
    }

    public function send(User $user, EmailMessage $emailMessage): bool
    {
        return $this->hasPermission($user, 'send_supplier_emails') || $this->manage($user);
    }

    /**
     * @param  list<UserRole>  $roles
     */
    private function hasAnyRole(User $user, array $roles): bool
    {
        return $user->hasAnyRole($roles);
    }

    private function hasPermission(User $user, string $permission): bool
    {
        return method_exists($user, 'hasPermission')
            ? $user->hasPermission($permission)
            : (method_exists($user, 'hasPermissionTo') && $user->hasPermissionTo($permission));
    }

    private function relatedOrder(EmailMessage $emailMessage): ?SupplierOrder
    {
        if ($emailMessage->relationLoaded('relatedSupplierOrder')) {
            return $emailMessage->relatedSupplierOrder;
        }

        return $emailMessage->relatedSupplierOrder()
            ->select(['id', 'company_id', 'supplier_id', 'status'])
            ->first();
    }
}
