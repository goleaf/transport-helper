<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\UploadManufacturerFormTemplateRequest;
use App\Models\FormTemplate;
use App\Services\Supply\ManufacturerForms\ManufacturerFormTemplateUploadService;
use Illuminate\Http\RedirectResponse;

class ManufacturerFormTemplateController extends Controller
{
    public function upload(UploadManufacturerFormTemplateRequest $request, FormTemplate $template, ManufacturerFormTemplateUploadService $service): RedirectResponse
    {
        $service->upload($template, $request->file('file'), $request->validated(), $request->user());

        return back()->with('status', 'Manufacturer form template uploaded.');
    }
}
