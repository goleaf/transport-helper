<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\StoreManualCarrierQuoteRequest;
use App\Models\Carrier;
use App\Models\SupplierOrder;
use App\Services\Supply\Transport\CarrierQuoteManualService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ManualCarrierQuoteController extends Controller
{
    public function create(SupplierOrder $order): View
    {
        return view('supply.transport.quotes.create-manual', [
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

    public function store(StoreManualCarrierQuoteRequest $request, CarrierQuoteManualService $quoteManualService): RedirectResponse
    {
        $result = $quoteManualService->createManualQuote($request->validated(), $request->user());

        return redirect()
            ->route('supply.transport.orders.quotes', $result['quote']->supplier_order_id)
            ->with('status', 'Manual carrier quote created.');
    }
}
