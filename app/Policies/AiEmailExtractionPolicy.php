<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\AiEmailExtraction;
use App\Models\User;

class AiEmailExtractionPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->review($user);
    }

    public function view(User $user, AiEmailExtraction $aiEmailExtraction): bool
    {
        return $this->review($user);
    }

    public function create(User $user): bool
    {
        return $this->review($user);
    }

    public function update(User $user, AiEmailExtraction $aiEmailExtraction): bool
    {
        return $this->review($user);
    }

    public function accept(User $user, AiEmailExtraction $aiEmailExtraction): bool
    {
        return $this->review($user);
    }

    public function reject(User $user, AiEmailExtraction $aiEmailExtraction): bool
    {
        return $this->review($user);
    }

    public function requestHumanReview(User $user, AiEmailExtraction $aiEmailExtraction): bool
    {
        return $this->review($user);
    }

    public function markNeedsReview(User $user, AiEmailExtraction $aiEmailExtraction): bool
    {
        return $this->review($user);
    }

    public function applyAsSupplierConfirmation(User $user, AiEmailExtraction $aiEmailExtraction): bool
    {
        return $user->hasAnyRole([UserRole::Admin, UserRole::SupplyManager])
            || $user->hasPermissionTo('apply_supplier_confirmations');
    }

    public function delete(User $user, AiEmailExtraction $aiEmailExtraction): bool
    {
        return false;
    }

    public function restore(User $user, AiEmailExtraction $aiEmailExtraction): bool
    {
        return false;
    }

    public function forceDelete(User $user, AiEmailExtraction $aiEmailExtraction): bool
    {
        return false;
    }

    private function review(User $user): bool
    {
        return $user->hasAnyRole([UserRole::Admin, UserRole::SupplyManager])
            || $user->hasPermissionTo('review_ai_extractions');
    }
}
