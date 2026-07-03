<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Carrier;
use App\Models\CarrierQuote;
use App\Models\SupplierOrder;
use Illuminate\Contracts\View\View;

class TransportQuoteController extends Controller
{
    public function index(): View
    {
        $quotes = CarrierQuote::query()
            ->select(['id', 'company_id', 'supplier_order_id', 'carrier_id', 'price', 'currency', 'pickup_date', 'delivery_date', 'reliability_score', 'calculated_score', 'score_explanation_json', 'status', 'created_at'])
            ->with(['supplierOrder:id,order_number', 'carrier:id,name'])
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('supply.transport.quotes.index', [
            'quotes' => $quotes,
            'supplierOrder' => null,
            'carriers' => Carrier::query()->select(['id', 'name'])->where('is_active', true)->orderBy('name')->limit(100)->get(),
            'auditLogsByQuoteId' => collect(),
        ]);
    }

    public function orderQuotes(SupplierOrder $supplierOrder): View
    {
        $supplierOrder->load(['supplier:id,name', 'logisticsRecords']);
        $quotes = CarrierQuote::query()
            ->select(['id', 'company_id', 'supplier_order_id', 'carrier_id', 'price', 'currency', 'pickup_date', 'delivery_date', 'reliability_score', 'calculated_score', 'score_explanation_json', 'status', 'created_at'])
            ->whereBelongsTo($supplierOrder)
            ->with(['supplierOrder:id,order_number', 'carrier:id,name'])
            ->latest('id')
            ->paginate(25)
            ->withQueryString();
        $quoteIds = $quotes->getCollection()->pluck('id');

        return view('supply.transport.quotes.index', [
            'quotes' => $quotes,
            'supplierOrder' => $supplierOrder,
            'carriers' => Carrier::query()->select(['id', 'name'])->where('company_id', $supplierOrder->company_id)->where('is_active', true)->orderBy('name')->limit(100)->get(),
            'auditLogsByQuoteId' => AuditLog::query()
                ->select(['id', 'event_type', 'auditable_id', 'user_id', 'created_at'])
                ->where('auditable_type', CarrierQuote::class)
                ->whereIn('auditable_id', $quoteIds)
                ->with('user:id,name')
                ->latest('id')
                ->get()
                ->groupBy('auditable_id'),
        ]);
    }
}
