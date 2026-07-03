<?php

namespace App\Policies;

use App\Models\FormAutofillFieldValue;
use App\Models\User;

class FormAutofillFieldValuePolicy
{
    public function accept(User $user, FormAutofillFieldValue $field): bool
    {
        return $this->review($user);
    }

    public function update(User $user, FormAutofillFieldValue $field): bool
    {
        return $this->review($user);
    }

    public function reject(User $user, FormAutofillFieldValue $field): bool
    {
        return $this->review($user);
    }

    private function review(User $user): bool
    {
        return $user->canManageSupplyWorkflow()
            || $user->hasPermissionTo('use_email_form_autofill');
    }
}
