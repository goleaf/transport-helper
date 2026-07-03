<?php

namespace App\Services\FormAutofill;

use App\Models\FormAutofillRun;
use App\Models\User;
use App\Services\Forms\FormAutofillApplyGateService;

class FormAutofillApplyService
{
    public function __construct(private readonly FormAutofillApplyGateService $applyGateService) {}

    /**
     * Compatibility method for older callers. It performs a readiness check only.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function apply(FormAutofillRun $run, User $user, array $options = []): array
    {
        return $this->applyGateService->check($run, $user);
    }
}
