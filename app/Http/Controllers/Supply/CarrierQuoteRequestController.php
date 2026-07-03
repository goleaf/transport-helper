<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\RequestCarrierQuotesRequest;
use App\Models\SupplierOrder;
use App\Services\Supply\CarrierQuoteRequestService;
use Illuminate\Http\RedirectResponse;

class CarrierQuoteRequestController extends Controller
{
    public function store(
        RequestCarrierQuotesRequest $request,
        SupplierOrder $supplierOrder,
        CarrierQuoteRequestService $quoteRequestService,
    ): RedirectResponse {
        $quoteRequestService->requestQuotes($supplierOrder, $request->validated(), $request->user());

        return redirect()
            ->route('supply.transport.orders.quotes.index', $supplierOrder)
            ->with('status', 'Carrier quote requests recorded.');
    }
}
