<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\StoreFormTemplateRequest;
use App\Models\Company;
use App\Models\FormTemplate;
use App\Services\Forms\FormTemplateService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class FormTemplateController extends Controller
{
    public function index(): View
    {
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
        return view('supply.forms.templates.create', [
            'companies' => Company::query()->select(['id', 'name'])->orderBy('name')->get(),
        ]);
    }

    public function store(StoreFormTemplateRequest $request, FormTemplateService $templateService): RedirectResponse
    {
        $template = $templateService->createTemplate($request->validated(), $request->user())['template'];

        return redirect()
            ->route('supply.forms.templates.show', $template)
            ->with('status', 'Form template created.');
    }

    public function show(FormTemplate $template): View
    {
        $template->load(['company:id,name', 'fields']);

        return view('supply.forms.templates.show', [
            'template' => $template,
        ]);
    }

    public function edit(FormTemplate $template): View
    {
        $template->load(['company:id,name', 'fields']);

        return view('supply.forms.templates.edit', [
            'template' => $template,
            'companies' => Company::query()->select(['id', 'name'])->orderBy('name')->get(),
        ]);
    }

    public function update(StoreFormTemplateRequest $request, FormTemplate $template, FormTemplateService $templateService): RedirectResponse
    {
        $template = $templateService->updateTemplate($template, $request->validated(), $request->user())['template'];

        return redirect()
            ->route('supply.forms.templates.show', $template)
            ->with('status', 'Form template updated.');
    }
}
