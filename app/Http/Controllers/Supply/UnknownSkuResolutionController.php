<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\CreateUnknownSkuResolutionRequest;
use App\Http\Requests\Supply\ResolveUnknownSkuRequest;
use App\Models\Company;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\UnknownSkuResolution;
use App\Services\Supply\MasterData\UnknownSkuResolutionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class UnknownSkuResolutionController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', UnknownSkuResolution::class);

        return view('supply.master-data.unknown-skus.index', [
            'resolutions' => UnknownSkuResolution::query()
                ->select(['id', 'company_id', 'supplier_id', 'unknown_sku', 'source_type', 'source_reference', 'status', 'resolved_product_id', 'resolution_type', 'created_at'])
                ->with(['company:id,name', 'supplier:id,name,code', 'resolvedProduct:id,sku,name'])
                ->latest('id')
                ->paginate(25)
                ->withQueryString(),
            'companies' => Company::query()->select(['id', 'name'])->orderBy('name')->limit(200)->get(),
            'suppliers' => Supplier::query()->select(['id', 'company_id', 'name', 'code'])->orderBy('name')->limit(500)->get(),
        ]);
    }

    public function show(UnknownSkuResolution $resolution): View
    {
        Gate::authorize('view', $resolution);
        $resolution->load(['company:id,name', 'supplier:id,name,code', 'resolvedProduct:id,sku,name', 'createdBy:id,name', 'resolvedBy:id,name']);

        return view('supply.master-data.unknown-skus.show', [
            'resolution' => $resolution,
            'products' => Product::query()->select(['id', 'company_id', 'sku', 'name'])->where('company_id', $resolution->company_id)->orderBy('sku')->limit(500)->get(),
        ]);
    }

    public function store(CreateUnknownSkuResolutionRequest $request, UnknownSkuResolutionService $service): RedirectResponse
    {
        $service->recordUnknown($request->validated(), $request->user());

        return redirect()->route('supply.master-data.unknown-skus.index')->with('status', 'Unknown SKU recorded.');
    }

    public function resolve(ResolveUnknownSkuRequest $request, UnknownSkuResolution $resolution, UnknownSkuResolutionService $service): RedirectResponse
    {
        $validated = $request->validated();

        match ($validated['resolution_type']) {
            'existing_product' => $service->resolveToProduct($resolution, Product::query()->findOrFail($validated['product_id']), $request->user(), $validated['reason']),
            'product_alias' => $service->createAliasResolution($resolution, Product::query()->findOrFail($validated['product_id']), $validated['alias_type'] ?? 'sku_alias', $request->user(), $validated['reason']),
            'product_change_request' => $service->createProductChangeRequest($resolution, $validated['requested_changes_json'] ?? [], $request->user(), $validated['reason']),
            'ignored' => $service->ignore($resolution, $request->user(), $validated['reason']),
        };

        return redirect()->route('supply.master-data.unknown-skus.show', $resolution)->with('status', 'Unknown SKU resolution updated.');
    }

    public function ignore(ResolveUnknownSkuRequest $request, UnknownSkuResolution $resolution, UnknownSkuResolutionService $service): RedirectResponse
    {
        $service->ignore($resolution, $request->user(), $request->validated()['reason']);

        return redirect()->route('supply.master-data.unknown-skus.show', $resolution)->with('status', 'Unknown SKU ignored with reason.');
    }
}
