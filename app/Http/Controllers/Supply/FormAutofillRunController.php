<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\ValidateAutofillRunRequest;
use App\Models\AuditLog;
use App\Models\FormAutofillRun;
use App\Services\Forms\FormAutofillReviewService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FormAutofillRunController extends Controller
{
    public function index(Request $request): View
    {
        $runs = FormAutofillRun::query()
            ->select(['id', 'company_id', 'email_message_id', 'form_template_id', 'status', 'confidence', 'created_at'])
            ->with([
                'emailMessage:id,subject,from_email,received_at',
                'formTemplate:id,name,context_type',
            ])
            ->withCount('fieldValues')
            ->when($request->string('status')->isNotEmpty(), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('supply.form-autofill-runs.index', [
            'runs' => $runs,
        ]);
    }

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
            'canApply' => false,
            'canApplySupplierConfirmation' => Gate::allows('applyAsSupplierConfirmation', $run),
        ]);
    }

    public function validateRun(ValidateAutofillRunRequest $request, FormAutofillRun $run, FormAutofillReviewService $reviewService): RedirectResponse
    {
        $reviewService->validateRun($run, $request->user(), $request->validated());

        return redirect()
            ->route('supply.form-autofill-runs.show', $run)
            ->with('status', 'Autofill run validated.');
    }
}
