<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\StoreTrendOverrideRequest;
use App\Models\Company;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\TrendOverride;
use App\Services\Supply\Forecasting\TrendOverrideService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class TrendOverrideController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', TrendOverride::class);

        return view('supply.forecasting.overrides.index', [
            'overrides' => TrendOverride::query()
                ->select(['id', 'company_id', 'supplier_id', 'product_id', 'category', 'trend_value', 'date_from', 'date_to', 'status', 'reason', 'created_by_user_id', 'approved_by_user_id', 'approved_at'])
                ->with(['company:id,name', 'supplier:id,name,code', 'product:id,sku,name,category', 'createdBy:id,name', 'approvedBy:id,name'])
                ->latest('date_from')
                ->latest('id')
                ->paginate(25)
                ->withQueryString(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', TrendOverride::class);

        return view('supply.forecasting.overrides.create', $this->formData());
    }

    public function store(StoreTrendOverrideRequest $request, TrendOverrideService $service): RedirectResponse
    {
        $result = $service->createOverride($request->validated(), $request->user());

        return redirect()->route('supply.forecasting.overrides.show', $result['override'])->with('status', 'Trend override created.');
    }

    public function show(TrendOverride $override): View
    {
        Gate::authorize('view', $override);

        $override->load(['company:id,name', 'supplier:id,name,code', 'product:id,sku,name,category', 'createdBy:id,name', 'approvedBy:id,name', 'revokedBy:id,name']);

        return view('supply.forecasting.overrides.show', ['override' => $override]);
    }

    public function submit(TrendOverride $override, TrendOverrideService $service): RedirectResponse
    {
        Gate::authorize('submit', $override);

        $service->submitForApproval($override, request()->user());

        return redirect()->route('supply.forecasting.overrides.show', $override)->with('status', 'Trend override submitted for approval.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'companies' => Company::query()->select(['id', 'name'])->orderBy('name')->limit(200)->get(),
            'suppliers' => Supplier::query()->select(['id', 'company_id', 'name', 'code'])->orderBy('name')->limit(500)->get(),
            'products' => Product::query()->select(['id', 'company_id', 'sku', 'name', 'category'])->orderBy('sku')->limit(1000)->get(),
        ];
    }
}
