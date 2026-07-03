<?php

namespace App\Http\Controllers\Supply;

use App\Enums\FormFieldType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\StoreFormTemplateRequest;
use App\Models\Company;
use App\Models\FormTemplate;
use App\Services\Forms\FormTemplateService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class FormTemplateController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', FormTemplate::class);

        $templates = FormTemplate::query()
            ->select(['id', 'company_id', 'name', 'code', 'context_type', 'format_type', 'version', 'is_active', 'created_at'])
            ->with('company:id,name')
            ->withCount('fields')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('supply.forms.templates.index', [
            'templates' => $templates,
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', FormTemplate::class);

        return view('supply.forms.templates.create', [
            'companies' => Company::query()->select(['id', 'name'])->orderBy('name')->get(),
        ]);
    }

    public function store(StoreFormTemplateRequest $request, FormTemplateService $templateService): RedirectResponse
    {
        Gate::authorize('create', FormTemplate::class);

        $template = $templateService->createTemplate($request->validated(), $request->user())['template'];

        return redirect()
            ->route('supply.forms.templates.show', $template)
            ->with('status', 'Form template created.');
    }

    public function show(FormTemplate $template): View
    {
        Gate::authorize('view', $template);

        $template
            ->load([
                'company:id,name',
                'supplier:id,name',
                'carrier:id,name',
                'fields:id,form_template_id,field_key,label,field_type,is_required,ai_extraction_hint,sort_order',
            ])
            ->loadCount(['fields', 'autofillRuns']);

        return view('supply.forms.templates.show', [
            'template' => $template,
            'fieldTypes' => FormFieldType::cases(),
        ]);
    }

    public function edit(FormTemplate $template): View
    {
        Gate::authorize('update', $template);

        $template->load(['company:id,name', 'fields']);

        return view('supply.forms.templates.edit', [
            'template' => $template,
            'companies' => Company::query()->select(['id', 'name'])->orderBy('name')->get(),
        ]);
    }

    public function update(StoreFormTemplateRequest $request, FormTemplate $template, FormTemplateService $templateService): RedirectResponse
    {
        Gate::authorize('update', $template);

        $template = $templateService->updateTemplate($template, $request->validated(), $request->user())['template'];

        return redirect()
            ->route('supply.forms.templates.show', $template)
            ->with('status', 'Form template updated.');
    }
}
