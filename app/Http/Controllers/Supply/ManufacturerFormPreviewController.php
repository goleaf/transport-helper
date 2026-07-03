<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\PreviewManufacturerFormRequest;
use App\Models\FormTemplate;
use App\Models\SupplierOrder;
use App\Services\Supply\ManufacturerForms\ManufacturerFormPreviewService;
use Illuminate\Contracts\View\View;

class ManufacturerFormPreviewController extends Controller
{
    public function store(PreviewManufacturerFormRequest $request, FormTemplate $template, ManufacturerFormPreviewService $service): View
    {
        $order = SupplierOrder::query()
            ->with(['supplier:id,name'])
            ->findOrFail($request->validated('supplier_order_id'));

        return view('supply.forms.manufacturer.preview', [
            'template' => $template,
            'order' => $order,
            'preview' => $service->preview($template, $order),
        ]);
    }
}
