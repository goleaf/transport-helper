<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\StoreManualSupplierConfirmationRequest;
use App\Models\SupplierConfirmation;
use App\Models\SupplierOrder;
use App\Services\Supply\Confirmations\SupplierConfirmationManualDataService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ManualSupplierConfirmationController extends Controller
{
    public function create(SupplierOrder $order): View
    {
        Gate::authorize('createManual', SupplierConfirmation::class);
        $order->load(['supplier:id,name', 'items.product:id,sku,manufacturer_sku,name,unit']);

        return view('supply.supplier-confirmations.create-manual', [
            'order' => $order,
        ]);
    }

    public function store(StoreManualSupplierConfirmationRequest $request, SupplierOrder $order, SupplierConfirmationManualDataService $service): RedirectResponse
    {
        $result = $service->applyManual($order, $request->validated(), $request->user());

        return redirect()
            ->route('supply.supplier-confirmations.show', $result['confirmation'])
            ->with('status', 'Supplier confirmation applied.');
    }
}
