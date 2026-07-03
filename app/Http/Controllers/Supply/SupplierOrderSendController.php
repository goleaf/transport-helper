<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\SendSupplierOrderEmailRequest;
use App\Models\SupplierOrder;
use App\Services\Supply\SupplierOrders\SupplierOrderSendService;
use Illuminate\Http\RedirectResponse;

class SupplierOrderSendController extends Controller
{
    public function store(
        SendSupplierOrderEmailRequest $request,
        SupplierOrder $order,
        SupplierOrderSendService $sendService,
    ): RedirectResponse {
        $result = $sendService->send($order, $request->validated(), $request->user());

        return redirect()
            ->route('supply.supplier-orders.show', $order)
            ->with('status', 'Email '.$result['email_message']->message_id.' sent.');
    }
}
