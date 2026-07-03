<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\ExportSupplierOrderRequest;
use App\Models\SupplierOrder;
use App\Services\Supply\SupplierOrderExportService;
use Illuminate\Http\RedirectResponse;

class SupplierOrderExportController extends Controller
{
    public function store(
        ExportSupplierOrderRequest $request,
        SupplierOrder $order,
        SupplierOrderExportService $exportService,
    ): RedirectResponse {
        $exportFile = $exportService->export($order, $request->user(), $request->validated());

        return redirect()
            ->route('supply.supplier-orders.show', $order)
            ->with('status', "Export {$exportFile->filename} created.");
    }
}
