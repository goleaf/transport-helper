<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\UpdateSupplierLifecycleRequest;
use App\Models\Supplier;
use App\Services\Supply\MasterData\SupplierLifecycleService;
use Illuminate\Http\RedirectResponse;

class SupplierLifecycleController extends Controller
{
    public function store(UpdateSupplierLifecycleRequest $request, Supplier $supplier, SupplierLifecycleService $service): RedirectResponse
    {
        $validated = $request->validated();
        $service->changeStatus($supplier, $validated['status'], $request->user(), $validated['reason'], $validated);

        return redirect()->route('supply.master-data.dashboard')->with('status', 'Supplier lifecycle status updated with audit.');
    }
}
