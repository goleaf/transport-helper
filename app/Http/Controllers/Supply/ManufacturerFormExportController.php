<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\ExportManufacturerFormRequest;
use App\Models\FormTemplate;
use App\Models\SupplierOrder;
use App\Services\Supply\ManufacturerForms\ManufacturerFormExportService;
use Illuminate\Http\RedirectResponse;

class ManufacturerFormExportController extends Controller
{
    public function store(ExportManufacturerFormRequest $request, SupplierOrder $order, ManufacturerFormExportService $service): RedirectResponse
    {
        $template = FormTemplate::query()->findOrFail($request->validated('form_template_id'));
        $service->export($order, $template, $request->validated(), $request->user());

        return back()->with('status', 'Manufacturer form export prepared.');
    }
}
