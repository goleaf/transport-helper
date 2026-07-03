<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\StoreFormTemplateFieldRequest;
use App\Models\FormTemplate;
use App\Services\Forms\FormTemplateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class FormTemplateFieldController extends Controller
{
    public function store(
        StoreFormTemplateFieldRequest $request,
        FormTemplate $template,
        FormTemplateService $templateService,
    ): RedirectResponse {
        Gate::authorize('addField', $template);

        $templateService->addField($template, $request->validated(), $request->user());

        return redirect()
            ->route('supply.forms.templates.show', $template)
            ->with('status', 'Template field created.');
    }
}
