<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\RejectCarrierQuoteRequest;
use App\Models\CarrierQuote;
use App\Services\Supply\Transport\CarrierSelectionService;
use Illuminate\Http\RedirectResponse;

class CarrierQuoteRejectionController extends Controller
{
    public function store(RejectCarrierQuoteRequest $request, CarrierQuote $quote, CarrierSelectionService $selectionService): RedirectResponse
    {
        $selectionService->reject($quote, $request->user(), $request->validated());

        return redirect()->route('supply.transport.orders.quotes', $quote->supplier_order_id)->with('status', 'Carrier quote rejected.');
    }
}
