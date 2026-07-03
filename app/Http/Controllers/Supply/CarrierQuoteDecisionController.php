<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\SelectCarrierQuoteRequest;
use App\Models\CarrierQuote;
use App\Services\Supply\CarrierSelectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CarrierQuoteDecisionController extends Controller
{
    public function select(
        SelectCarrierQuoteRequest $request,
        CarrierQuote $quote,
        CarrierSelectionService $selectionService,
    ): RedirectResponse {
        $selectionService->select($quote, $request->user(), $request->validated());

        return redirect()
            ->route('supply.transport.orders.quotes.index', $quote->supplier_order_id)
            ->with('status', 'Carrier selected.');
    }

    public function reject(Request $request, CarrierQuote $quote, CarrierSelectionService $selectionService): RedirectResponse
    {
        abort_unless($request->user()?->canManageLogisticsWorkflow(), 403);

        $selectionService->reject($quote, $request->user());

        return redirect()
            ->route('supply.transport.orders.quotes.index', $quote->supplier_order_id)
            ->with('status', 'Carrier quote rejected.');
    }
}
