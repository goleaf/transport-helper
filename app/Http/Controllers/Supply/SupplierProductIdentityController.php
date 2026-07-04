<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\StoreSupplierProductIdentityRequest;
use App\Models\Company;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierProductIdentity;
use App\Services\Supply\MasterData\SupplierProductIdentityService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class SupplierProductIdentityController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', SupplierProductIdentity::class);

        return view('supply.master-data.mappings.index', [
            'identities' => SupplierProductIdentity::query()
                ->select(['id', 'company_id', 'supplier_id', 'product_id', 'supplier_sku', 'manufacturer_sku', 'supplier_product_name', 'barcode', 'status', 'confidence', 'created_at'])
                ->with(['company:id,name', 'supplier:id,name,code', 'product:id,sku,name'])
                ->latest('id')
                ->paginate(25)
                ->withQueryString(),
            'companies' => Company::query()->select(['id', 'name'])->orderBy('name')->limit(200)->get(),
            'suppliers' => Supplier::query()->select(['id', 'company_id', 'name', 'code'])->orderBy('name')->limit(500)->get(),
            'products' => Product::query()->select(['id', 'company_id', 'sku', 'name'])->orderBy('sku')->limit(500)->get(),
        ]);
    }

    public function store(StoreSupplierProductIdentityRequest $request, SupplierProductIdentityService $service): RedirectResponse
    {
        $service->createMapping($request->validated(), $request->user());

        return redirect()->route('supply.master-data.supplier-product-identities.index')->with('status', 'Supplier product mapping created.');
    }
}
