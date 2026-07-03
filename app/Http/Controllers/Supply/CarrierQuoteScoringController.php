<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\ScoreCarrierQuotesRequest;
use App\Models\SupplierOrder;
use App\Services\Supply\Transport\CarrierQuoteComparisonService;
use Illuminate\Http\RedirectResponse;

class CarrierQuoteScoringController extends Controller
{
    public function store(ScoreCarrierQuotesRequest $request, SupplierOrder $order, CarrierQuoteComparisonService $comparisonService): RedirectResponse
    {
        $comparisonService->compareForOrder($order, $request->validated());

        return redirect()->route('supply.transport.orders.quotes', $order)->with('status', 'Carrier quotes scored.');
    }
}
