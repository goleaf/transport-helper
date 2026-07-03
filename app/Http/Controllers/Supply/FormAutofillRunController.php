<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\FormAutofillRun;
use App\Services\FormAutofill\FormAutofillReviewService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FormAutofillRunController extends Controller
{
    public function show(FormAutofillRun $run): View
    {
        $run->load([
            'emailMessage.attachments',
            'emailMessage.relatedSupplier:id,name',
            'emailMessage.relatedSupplierOrder:id,order_number',
            'formTemplate.fields',
            'fieldValues',
            'outputs',
        ]);

        $auditLogs = AuditLog::query()
            ->select(['id', 'event_type', 'user_id', 'created_at'])
            ->where('auditable_type', $run::class)
            ->where('auditable_id', $run->id)
            ->with('user:id,name')
            ->orderByDesc('id')
            ->get();

        return view('supply.form-autofill-runs.show', [
            'run' => $run,
            'auditLogs' => $auditLogs,
            'canApply' => $run->status->value === 'validated',
        ]);
    }

    public function validateRun(Request $request, FormAutofillRun $run, FormAutofillReviewService $reviewService): RedirectResponse
    {
        abort_unless($request->user()?->canManageSupplyWorkflow(), 403);

        $reviewService->validateRun($run, $request->user());

        return redirect()
            ->route('supply.form-autofill-runs.show', $run)
            ->with('status', 'Autofill run validated.');
    }
}
