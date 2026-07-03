<?php

namespace App\Policies;

use App\Models\AiEmailExtraction;
use App\Models\User;

class AiEmailExtractionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, AiEmailExtraction $aiEmailExtraction): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->canManageSupplyWorkflow();
    }

    public function update(User $user, AiEmailExtraction $aiEmailExtraction): bool
    {
        return $user->canManageSupplyWorkflow();
    }

    public function accept(User $user, AiEmailExtraction $aiEmailExtraction): bool
    {
        return $user->canManageSupplyWorkflow();
    }

    public function reject(User $user, AiEmailExtraction $aiEmailExtraction): bool
    {
        return $user->canManageSupplyWorkflow();
    }

    public function requestHumanReview(User $user, AiEmailExtraction $aiEmailExtraction): bool
    {
        return $user->canManageSupplyWorkflow();
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
}
