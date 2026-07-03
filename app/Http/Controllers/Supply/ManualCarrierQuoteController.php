<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\StoreManualCarrierQuoteRequest;
use App\Services\Supply\CarrierQuoteApplicationService;
use Illuminate\Http\RedirectResponse;

class ManualCarrierQuoteController extends Controller
{
    public function store(StoreManualCarrierQuoteRequest $request, CarrierQuoteApplicationService $quoteApplicationService): RedirectResponse
    {
        $validated = $request->validated();
        $result = $quoteApplicationService->create(array_merge($validated, [
            'source_type' => 'manual',
            'created_by_user_id' => $request->user()?->id,
        ]));

        return redirect()
            ->route('supply.transport.orders.quotes.index', $result['quote']->supplier_order_id)
            ->with('status', 'Manual carrier quote created.');
    }
}
