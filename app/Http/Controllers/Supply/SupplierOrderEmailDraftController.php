<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\PrepareSupplierOrderEmailRequest;
use App\Models\SupplierOrder;
use App\Services\Supply\SupplierOrders\SupplierOrderEmailDraftService;
use Illuminate\Http\RedirectResponse;

class SupplierOrderEmailDraftController extends Controller
{
    public function store(
        PrepareSupplierOrderEmailRequest $request,
        SupplierOrder $order,
        SupplierOrderEmailDraftService $draftService,
    ): RedirectResponse {
        $result = $draftService->prepareDraft($order, $request->validated(), $request->user());

        return redirect()
            ->route('supply.supplier-orders.show', $order)
            ->with('status', 'Email draft '.$result['email_message']->id.' prepared.');
    }
}
