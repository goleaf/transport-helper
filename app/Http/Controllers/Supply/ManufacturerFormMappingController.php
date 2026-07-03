<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\SaveManufacturerFormMappingRequest;
use App\Models\FormTemplate;
use App\Services\Supply\ManufacturerForms\ManufacturerFormMappingService;
use Illuminate\Http\RedirectResponse;

class ManufacturerFormMappingController extends Controller
{
    public function store(SaveManufacturerFormMappingRequest $request, FormTemplate $template, ManufacturerFormMappingService $service): RedirectResponse
    {
        $service->saveMapping($template, $request->validated('mapping'), $request->user());

        return back()->with('status', 'Manufacturer form mapping saved.');
    }
}
