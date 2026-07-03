<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\CreateEmailFormAutofillRunRequest;
use App\Models\EmailMessage;
use App\Models\FormTemplate;
use App\Services\Forms\EmailFormAutofillService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class EmailFormAutofillController extends Controller
{
    public function create(EmailMessage $email): View
    {
        $email->load(['relatedSupplier:id,name', 'relatedSupplierOrder:id,order_number']);

        $templates = FormTemplate::query()
            ->select(['id', 'company_id', 'name', 'code', 'context_type', 'is_active'])
            ->where('company_id', $email->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('supply.form-autofill.create', [
            'email' => $email,
            'templates' => $templates,
        ]);
    }

    public function preview(
        CreateEmailFormAutofillRunRequest $request,
        EmailMessage $email,
        EmailFormAutofillService $autofillService,
    ): RedirectResponse {
        return $this->store($request, $email, $autofillService);
    }

    public function store(
        CreateEmailFormAutofillRunRequest $request,
        EmailMessage $email,
        EmailFormAutofillService $autofillService,
    ): RedirectResponse {
        $template = FormTemplate::query()
            ->where('company_id', $email->company_id)
            ->findOrFail($request->integer('form_template_id'));

        $result = $autofillService->createAutofillRun($email, $template, $request->validated(), $request->user());

        return redirect()
            ->route('supply.form-autofill-runs.show', $result['run'])
            ->with('status', 'Autofill preview generated.');
    }
}
