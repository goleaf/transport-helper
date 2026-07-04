<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\StoreProductAliasRequest;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductAlias;
use App\Services\Supply\MasterData\ProductIdentityService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ProductAliasController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', ProductAlias::class);

        return view('supply.master-data.aliases.product-index', [
            'aliases' => ProductAlias::query()
                ->select(['id', 'company_id', 'product_id', 'alias', 'alias_type', 'source_type', 'status', 'confidence', 'created_by_user_id', 'created_at'])
                ->with(['company:id,name', 'product:id,sku,name', 'createdBy:id,name'])
                ->latest('id')
                ->paginate(25)
                ->withQueryString(),
            'companies' => Company::query()->select(['id', 'name'])->orderBy('name')->limit(200)->get(),
            'products' => Product::query()->select(['id', 'company_id', 'sku', 'name'])->with('company:id,name')->orderBy('sku')->limit(500)->get(),
        ]);
    }

    public function store(StoreProductAliasRequest $request, ProductIdentityService $service): RedirectResponse
    {
        $service->createAlias($request->validated(), $request->user());

        return redirect()->route('supply.master-data.product-aliases.index')->with('status', 'Product alias created for review or active use.');
    }
}
