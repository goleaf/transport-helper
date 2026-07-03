<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\ApproveSupplierOrderEmailRequest;
use App\Models\SupplierOrder;
use App\Services\Supply\SupplierOrders\SupplierOrderEmailApprovalService;
use Illuminate\Http\RedirectResponse;

class SupplierOrderEmailApprovalController extends Controller
{
    public function store(
        ApproveSupplierOrderEmailRequest $request,
        SupplierOrder $order,
        SupplierOrderEmailApprovalService $approvalService,
    ): RedirectResponse {
        $result = $approvalService->approveEmail($order, $request->validated(), $request->user());

        return redirect()
            ->route('supply.supplier-orders.show', $order)
            ->with('status', 'Email draft '.$result['email_message']->id.' approved.');
    }
}
