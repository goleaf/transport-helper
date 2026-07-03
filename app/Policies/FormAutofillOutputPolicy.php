<?php

namespace App\Policies;

use App\Models\FormAutofillOutput;
use App\Models\User;

class FormAutofillOutputPolicy
{
    public function view(User $user, FormAutofillOutput $output): bool
    {
        return $user->canManageSupplyWorkflow()
            || $user->hasPermissionTo('use_email_form_autofill');
    }

    public function download(User $user, FormAutofillOutput $output): bool
    {
        return $this->view($user, $output);
    }
}
