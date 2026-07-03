<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\StoreSupplierProductPriceRequest;
use App\Models\Company;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierProductPrice;
use App\Services\Supply\Procurement\SupplierProductPriceService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class SupplierProductPriceController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', SupplierProductPrice::class);

        return view('supply.procurement.prices.index', [
            'prices' => SupplierProductPrice::query()
                ->select(['id', 'company_id', 'supplier_id', 'product_id', 'currency', 'unit_price', 'valid_from', 'valid_to', 'status', 'created_by_user_id'])
                ->with(['company:id,name', 'supplier:id,name', 'product:id,sku,name', 'createdBy:id,name'])
                ->latest('id')
                ->paginate(25)
                ->withQueryString(),
            'companies' => Company::query()->select(['id', 'name'])->orderBy('name')->limit(200)->get(),
            'suppliers' => Supplier::query()->select(['id', 'company_id', 'name'])->orderBy('name')->limit(300)->get(),
            'products' => Product::query()->select(['id', 'company_id', 'sku', 'name'])->orderBy('sku')->limit(500)->get(),
        ]);
    }

    public function store(StoreSupplierProductPriceRequest $request, SupplierProductPriceService $service): RedirectResponse
    {
        $result = $service->createPrice($request->validated(), $request->user());
        $message = $result['warnings'] === [] ? 'Supplier product price created.' : 'Supplier product price created with warnings.';

        return redirect()->route('supply.procurement.prices.index')->with('status', $message);
    }
}
