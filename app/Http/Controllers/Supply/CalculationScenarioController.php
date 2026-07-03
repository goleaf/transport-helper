<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Models\CalculationScenario;
use App\Models\Company;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class CalculationScenarioController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', CalculationScenario::class);

        return view('supply.forecasting.scenarios.index', [
            'scenarios' => CalculationScenario::query()
                ->select(['id', 'company_id', 'supplier_id', 'name', 'status', 'simulation_mode', 'formula_version', 'summary_json', 'warnings_json', 'created_by_user_id', 'simulated_at', 'created_at'])
                ->with(['company:id,name', 'supplier:id,name,code', 'createdBy:id,name'])
                ->withCount('items')
                ->latest('created_at')
                ->paginate(25)
                ->withQueryString(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('simulate', CalculationScenario::class);

        return view('supply.forecasting.scenarios.create', [
            'companies' => Company::query()->select(['id', 'name'])->orderBy('name')->limit(200)->get(),
            'suppliers' => Supplier::query()->select(['id', 'company_id', 'name', 'code'])->orderBy('name')->limit(500)->get(),
            'products' => Product::query()->select(['id', 'company_id', 'sku', 'name', 'category'])->orderBy('sku')->limit(1000)->get(),
            'scenarios' => CalculationScenario::query()->select(['id', 'name', 'status'])->latest('id')->limit(50)->get(),
        ]);
    }

    public function show(CalculationScenario $scenario): View
    {
        Gate::authorize('view', $scenario);

        $scenario->load([
            'company:id,name',
            'supplier:id,name,code',
            'createdBy:id,name',
            'items' => fn ($query) => $query
                ->select(['id', 'calculation_scenario_id', 'product_id', 'status', 'base_recommended_quantity', 'simulated_recommended_quantity', 'difference_quantity', 'trend_used', 'seasonality_factor', 'manual_trend_override_id', 'applied_profile_id', 'warnings_json', 'requires_human_review'])
                ->with(['product:id,sku,name,category', 'manualTrendOverride:id,trend_value,reason,status', 'appliedProfile:id,name'])
                ->orderBy('id'),
        ]);

        return view('supply.forecasting.scenarios.show', ['scenario' => $scenario]);
    }
}
