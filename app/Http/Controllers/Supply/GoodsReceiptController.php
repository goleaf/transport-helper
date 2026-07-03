<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\RecordGoodsReceiptRequest;
use App\Models\LogisticsRecord;
use App\Services\Supply\Logistics\LogisticsReceivingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class GoodsReceiptController extends Controller
{
    public function create(LogisticsRecord $record): View
    {
        Gate::authorize('recordReceipt', $record);

        $record->loadMissing('supplierOrder.items.product:id,sku,name,unit', 'supplier:id,name');

        return view('supply.logistics.receive', [
            'record' => $record,
            'order' => $record->supplierOrder,
        ]);
    }

    public function store(
        RecordGoodsReceiptRequest $request,
        LogisticsRecord $record,
        LogisticsReceivingService $receivingService,
    ): RedirectResponse {
        $receivingService->recordReceipt($record->supplierOrder, $request->validated(), $request->user());

        return redirect()
            ->route('supply.logistics.show', $record)
            ->with('status', 'Goods receipt recorded.');
    }
}
