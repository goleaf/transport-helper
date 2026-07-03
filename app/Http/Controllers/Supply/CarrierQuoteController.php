<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Models\Carrier;
use App\Models\CarrierQuote;
use App\Models\SupplierOrder;
use App\Services\Supply\Transport\CarrierQuoteComparisonService;
use Illuminate\Contracts\View\View;

class CarrierQuoteController extends Controller
{
    public function index(): View
    {
        return view('supply.transport.quotes.index', [
            'quotes' => CarrierQuote::query()
                ->select(['id', 'company_id', 'supplier_order_id', 'carrier_id', 'price', 'currency', 'pickup_date', 'delivery_date', 'transit_days', 'calculated_score', 'status', 'source_type', 'created_at'])
                ->with(['supplierOrder:id,order_number,supplier_id', 'supplierOrder.supplier:id,name', 'carrier:id,name'])
                ->latest('id')
                ->paginate(25)
                ->withQueryString(),
            'supplierOrder' => null,
            'carriers' => collect(),
            'auditLogsByQuoteId' => collect(),
            'comparison' => null,
        ]);
    }

    public function show(CarrierQuote $quote): View
    {
        $quote->load([
            'supplierOrder.supplier',
            'supplierOrder.logisticsRecords:id,supplier_order_id,selected_carrier_quote_id,status,pickup_date,delivery_date,actual_received_date',
            'carrier',
            'emailMessage:id,subject',
            'aiEmailExtraction:id,email_message_id,accepted_at,rejected_at',
            'formAutofillRun:id,status',
        ]);

        return view('supply.transport.quotes.show', ['quote' => $quote]);
    }

    public function forSupplierOrder(SupplierOrder $order, CarrierQuoteComparisonService $comparisonService): View
    {
        $order->load(['supplier:id,name', 'logisticsRecords.selectedCarrierQuote']);
        $comparison = $comparisonService->compareForOrder($order, []);

        return view('supply.transport.quotes.for-order', [
            'supplierOrder' => $order,
            'quotes' => CarrierQuote::query()
                ->select(['id', 'company_id', 'supplier_order_id', 'carrier_id', 'price', 'currency', 'pickup_date', 'delivery_date', 'transit_days', 'reliability_score', 'calculated_score', 'score_explanation_json', 'status', 'warnings_json'])
                ->whereBelongsTo($order)
                ->with(['carrier:id,name'])
                ->orderByDesc('calculated_score')
                ->paginate(25)
                ->withQueryString(),
            'comparison' => $comparison,
            'carriers' => Carrier::query()
                ->select(['id', 'name'])
                ->where('company_id', $order->company_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->limit(100)
                ->get(),
        ]);
    }
}
