<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\CheckAutofillApplyGateRequest;
use App\Models\FormAutofillRun;
use App\Services\Forms\FormAutofillApplyGateService;
use Illuminate\Http\RedirectResponse;

class FormAutofillApplyGateController extends Controller
{
    public function __invoke(CheckAutofillApplyGateRequest $request, FormAutofillRun $run, FormAutofillApplyGateService $gateService): RedirectResponse
    {
        $result = $gateService->check($run, $request->user());

        return redirect()
            ->route('supply.form-autofill-runs.show', $run)
            ->with('application_gate', $result)
            ->with('status', $result['message']);
    }
}
