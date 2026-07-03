<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\ApplyAutofillRunRequest;
use App\Models\FormAutofillRun;
use App\Services\FormAutofill\FormAutofillApplyService;
use Illuminate\Http\RedirectResponse;

class FormAutofillApplyController extends Controller
{
    public function __invoke(ApplyAutofillRunRequest $request, FormAutofillRun $run, FormAutofillApplyService $applyService): RedirectResponse
    {
        $applyService->apply($run, $request->user(), $request->validated());

        return redirect()
            ->route('supply.form-autofill-runs.show', $run)
            ->with('status', 'Autofill run applied.');
    }
}
