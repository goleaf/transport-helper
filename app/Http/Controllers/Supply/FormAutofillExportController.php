<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\ExportAutofillRunRequest;
use App\Models\FormAutofillRun;
use App\Services\FormAutofill\FormRenderService;
use Illuminate\Http\RedirectResponse;

class FormAutofillExportController extends Controller
{
    public function __invoke(ExportAutofillRunRequest $request, FormAutofillRun $run, FormRenderService $renderService): RedirectResponse
    {
        $format = $request->string('format')->toString();

        match ($format) {
            'json' => $renderService->exportJson($run, $request->user()),
            'csv' => $renderService->exportCsv($run, $request->user()),
            'internal_html' => $renderService->renderInternalHtml($run, $request->user()),
            default => $renderService->preparePlaceholder($format),
        };

        return redirect()
            ->route('supply.form-autofill-runs.show', $run)
            ->with('status', 'Autofill run exported.');
    }
}
