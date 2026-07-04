<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\StoreSupplierAliasRequest;
use App\Models\Company;
use App\Models\Supplier;
use App\Models\SupplierAlias;
use App\Services\Supply\MasterData\SupplierIdentityService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class SupplierAliasController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', SupplierAlias::class);

        return view('supply.master-data.aliases.supplier-index', [
            'aliases' => SupplierAlias::query()
                ->select(['id', 'company_id', 'supplier_id', 'alias', 'alias_type', 'source_type', 'status', 'confidence', 'created_by_user_id', 'created_at'])
                ->with(['company:id,name', 'supplier:id,name,code', 'createdBy:id,name'])
                ->latest('id')
                ->paginate(25)
                ->withQueryString(),
            'companies' => Company::query()->select(['id', 'name'])->orderBy('name')->limit(200)->get(),
            'suppliers' => Supplier::query()->select(['id', 'company_id', 'name', 'code'])->with('company:id,name')->orderBy('name')->limit(500)->get(),
        ]);
    }

    public function store(StoreSupplierAliasRequest $request, SupplierIdentityService $service): RedirectResponse
    {
        $service->createAlias($request->validated(), $request->user());

        return redirect()->route('supply.master-data.supplier-aliases.index')->with('status', 'Supplier alias created for review or active use.');
    }
}
