<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\StoreFormTemplateFieldRequest;
use App\Models\FormTemplate;
use App\Services\FormAutofill\FormTemplateService;
use Illuminate\Http\RedirectResponse;

class FormTemplateFieldController extends Controller
{
    public function store(
        StoreFormTemplateFieldRequest $request,
        FormTemplate $template,
        FormTemplateService $templateService,
    ): RedirectResponse {
        $templateService->createField($template, $request->validated());

        return redirect()
            ->route('supply.forms.templates.show', $template)
            ->with('status', 'Template field created.');
    }
}
