<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\FormAutofillRun;
use App\Models\User;

class FormAutofillRunPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            UserRole::Admin,
            UserRole::SupplyManager,
            UserRole::LogisticsManager,
            UserRole::Accountant,
            UserRole::Viewer,
        ]);
    }

    public function view(User $user, FormAutofillRun $formAutofillRun): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([UserRole::Admin, UserRole::SupplyManager])
            || $user->hasPermissionTo('use_email_form_autofill');
    }

    public function update(User $user, FormAutofillRun $formAutofillRun): bool
    {
        return $this->review($user, $formAutofillRun);
    }

    public function review(User $user, FormAutofillRun $formAutofillRun): bool
    {
        return $user->hasAnyRole([UserRole::Admin, UserRole::SupplyManager])
            || $user->hasPermissionTo('review_ai_extractions')
            || $user->hasPermissionTo('use_email_form_autofill');
    }

    public function apply(User $user, FormAutofillRun $formAutofillRun): bool
    {
        return $this->checkApplyGate($user, $formAutofillRun);
    }

    public function validateRun(User $user, FormAutofillRun $formAutofillRun): bool
    {
        return $this->review($user, $formAutofillRun);
    }

    public function checkApplyGate(User $user, FormAutofillRun $formAutofillRun): bool
    {
        return $user->hasAnyRole([UserRole::Admin])
            || $user->hasPermissionTo('apply_email_form_autofill');
    }

    public function reject(User $user, FormAutofillRun $formAutofillRun): bool
    {
        return $this->review($user, $formAutofillRun);
    }

    public function export(User $user, FormAutofillRun $formAutofillRun): bool
    {
        return $this->view($user, $formAutofillRun);
    }

    public function delete(User $user, FormAutofillRun $formAutofillRun): bool
    {
        return false;
    }

    public function restore(User $user, FormAutofillRun $formAutofillRun): bool
    {
        return false;
    }

    public function forceDelete(User $user, FormAutofillRun $formAutofillRun): bool
    {
        return false;
    }
}
