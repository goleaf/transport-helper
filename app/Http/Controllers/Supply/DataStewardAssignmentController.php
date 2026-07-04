<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\AssignDataStewardRequest;
use App\Models\Company;
use App\Models\DataStewardAssignment;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Supply\MasterData\DataStewardService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class DataStewardAssignmentController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', DataStewardAssignment::class);

        return view('supply.master-data.stewards.index', [
            'assignments' => DataStewardAssignment::query()
                ->select(['id', 'company_id', 'user_id', 'stewardship_type', 'supplier_id', 'product_id', 'category', 'is_active', 'notes', 'assigned_by_user_id', 'created_at'])
                ->with(['company:id,name', 'user:id,name,email', 'supplier:id,name', 'product:id,sku,name', 'assignedBy:id,name'])
                ->latest('id')
                ->paginate(25)
                ->withQueryString(),
            'companies' => Company::query()->select(['id', 'name'])->orderBy('name')->limit(200)->get(),
            'users' => User::query()->select(['id', 'name', 'email'])->orderBy('name')->limit(500)->get(),
            'suppliers' => Supplier::query()->select(['id', 'company_id', 'name'])->orderBy('name')->limit(500)->get(),
            'products' => Product::query()->select(['id', 'company_id', 'sku', 'name'])->orderBy('sku')->limit(500)->get(),
        ]);
    }

    public function store(AssignDataStewardRequest $request, DataStewardService $service): RedirectResponse
    {
        $service->assign($request->validated(), $request->user());

        return redirect()->route('supply.master-data.stewards.index')->with('status', 'Data steward assigned.');
    }
}
