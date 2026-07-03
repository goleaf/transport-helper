<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\ValidateAutofillRunRequest;
use App\Models\FormAutofillRun;
use App\Services\Forms\FormAutofillReviewService;
use Illuminate\Http\RedirectResponse;

class FormAutofillRunValidationController extends Controller
{
    public function store(ValidateAutofillRunRequest $request, FormAutofillRun $run, FormAutofillReviewService $reviewService): RedirectResponse
    {
        $reviewService->validateRun($run, $request->user(), $request->validated());

        return redirect()
            ->route('supply.form-autofill-runs.show', $run)
            ->with('status', 'Autofill run validated.');
    }
}
