<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\UpdateAutofillFieldValueRequest;
use App\Models\FormAutofillFieldValue;
use App\Models\FormAutofillRun;
use App\Services\FormAutofill\FormAutofillReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FormAutofillFieldReviewController extends Controller
{
    public function accept(Request $request, FormAutofillRun $run, FormAutofillFieldValue $field, FormAutofillReviewService $reviewService): RedirectResponse
    {
        abort_unless($request->user()?->canManageSupplyWorkflow(), 403);

        $this->ensureFieldBelongsToRun($run, $field);
        $reviewService->acceptField($field, $request->user());

        return redirect()->route('supply.form-autofill-runs.show', $run)->with('status', 'Field accepted.');
    }

    public function update(UpdateAutofillFieldValueRequest $request, FormAutofillRun $run, FormAutofillFieldValue $field, FormAutofillReviewService $reviewService): RedirectResponse
    {
        $this->ensureFieldBelongsToRun($run, $field);
        $reviewService->updateField($field, $request->validated(), $request->user());

        return redirect()->route('supply.form-autofill-runs.show', $run)->with('status', 'Field updated.');
    }

    public function reject(Request $request, FormAutofillRun $run, FormAutofillFieldValue $field, FormAutofillReviewService $reviewService): RedirectResponse
    {
        abort_unless($request->user()?->canManageSupplyWorkflow(), 403);

        $this->ensureFieldBelongsToRun($run, $field);
        $reviewService->rejectField($field, $request->user());

        return redirect()->route('supply.form-autofill-runs.show', $run)->with('status', 'Field rejected.');
    }

    private function ensureFieldBelongsToRun(FormAutofillRun $run, FormAutofillFieldValue $field): void
    {
        abort_unless($field->form_autofill_run_id === $run->id, 404);
    }
}
