<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\PrepareCarrierQuoteRequestRequest;
use App\Models\Carrier;
use App\Models\SupplierOrder;
use App\Services\Supply\Transport\CarrierQuoteRequestService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class CarrierQuoteRequestController extends Controller
{
    public function create(SupplierOrder $order): View
    {
        return view('supply.transport.quote-requests.create', [
            'supplierOrder' => $order->load('supplier:id,name'),
            'carriers' => Carrier::query()
                ->select(['id', 'name'])
                ->where('company_id', $order->company_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->limit(100)
                ->get(),
        ]);
    }

    public function store(
        PrepareCarrierQuoteRequestRequest $request,
        SupplierOrder $order,
        CarrierQuoteRequestService $quoteRequestService,
    ): RedirectResponse {
        $validated = $request->validated();
        $quoteRequestService->prepareRequests($order, $validated['carrier_ids'], $validated, $request->user());

        return redirect()
            ->route('supply.transport.orders.quotes', $order)
            ->with('status', 'Carrier quote request drafts prepared.');
    }
}
