<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\UpdateProductLifecycleRequest;
use App\Models\Product;
use App\Services\Supply\MasterData\ProductLifecycleService;
use Illuminate\Http\RedirectResponse;

class ProductLifecycleController extends Controller
{
    public function store(UpdateProductLifecycleRequest $request, Product $product, ProductLifecycleService $service): RedirectResponse
    {
        $validated = $request->validated();
        $service->changeStatus($product, $validated['status'], $request->user(), $validated['reason'], $validated);

        return redirect()->route('supply.master-data.dashboard')->with('status', 'Product lifecycle status updated with audit.');
    }
}
