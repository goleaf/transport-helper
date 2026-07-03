<?php

namespace App\Http\Controllers\Supply;

use App\Exceptions\NotConfiguredYetException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\ExportSupplierOrderRequest;
use App\Models\SupplierOrder;
use App\Services\Supply\SupplierOrders\SupplierOrderExportService;
use Illuminate\Http\RedirectResponse;

class SupplierOrderExportController extends Controller
{
    public function store(
        ExportSupplierOrderRequest $request,
        SupplierOrder $order,
        SupplierOrderExportService $exportService,
    ): RedirectResponse {
        try {
            $result = $exportService->export(
                $order,
                (string) $request->validated('format'),
                $request->validated(),
                $request->user(),
            );
        } catch (NotConfiguredYetException $exception) {
            return redirect()
                ->route('supply.supplier-orders.show', $order)
                ->withErrors(['format' => $exception->getMessage()]);
        }

        return redirect()
            ->route('supply.supplier-orders.show', $order)
            ->with('status', "Export {$result['filename']} created.");
    }
}
