<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\SelectCarrierQuoteRequest;
use App\Models\CarrierQuote;
use App\Services\Supply\Transport\CarrierSelectionService;
use Illuminate\Http\RedirectResponse;

class CarrierSelectionController extends Controller
{
    public function store(SelectCarrierQuoteRequest $request, CarrierQuote $quote, CarrierSelectionService $selectionService): RedirectResponse
    {
        $selectionService->select($quote, $request->user(), $request->validated());

        return redirect()->route('supply.transport.orders.quotes', $quote->supplier_order_id)->with('status', 'Carrier selected.');
    }
}
