<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\ExportAutofillRunRequest;
use App\Models\FormAutofillRun;
use App\Services\Forms\FormAutofillExportService;
use Illuminate\Http\RedirectResponse;

class FormAutofillExportController extends Controller
{
    public function __invoke(ExportAutofillRunRequest $request, FormAutofillRun $run, FormAutofillExportService $exportService): RedirectResponse
    {
        $format = $request->string('format')->toString();
        $exportService->export($run, $format, $request->validated(), $request->user());

        return redirect()
            ->route('supply.form-autofill-runs.show', $run)
            ->with('status', 'Autofill run exported.');
    }
}
