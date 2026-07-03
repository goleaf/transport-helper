<?php

namespace App\Http\Controllers\Supply;

use App\Enums\SalesExclusionRuleType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\StoreSalesExclusionRuleRequest;
use App\Models\Company;
use App\Models\Product;
use App\Models\SalesExclusionRule;
use App\Models\Supplier;
use App\Services\Supply\Forecasting\SalesExclusionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class SalesExclusionRuleController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', SalesExclusionRule::class);

        return view('supply.forecasting.exclusions.index', [
            'rules' => SalesExclusionRule::query()
                ->select(['id', 'company_id', 'supplier_id', 'product_id', 'category', 'rule_type', 'date_from', 'date_to', 'applies_to', 'reason', 'is_active', 'created_by_user_id'])
                ->with(['company:id,name', 'supplier:id,name,code', 'product:id,sku,name,category', 'createdBy:id,name'])
                ->latest('date_from')
                ->latest('id')
                ->paginate(25)
                ->withQueryString(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', SalesExclusionRule::class);

        return view('supply.forecasting.exclusions.create', $this->formData() + ['rule' => null]);
    }

    public function store(StoreSalesExclusionRuleRequest $request, SalesExclusionService $service): RedirectResponse
    {
        $result = $service->createRule($request->validated(), $request->user());

        return redirect()->route('supply.forecasting.exclusions.show', $result['rule'])->with('status', 'Sales exclusion rule created.');
    }

    public function show(SalesExclusionRule $rule): View
    {
        Gate::authorize('view', $rule);

        $rule->load(['company:id,name', 'supplier:id,name,code', 'product:id,sku,name,category', 'createdBy:id,name', 'approvedBy:id,name']);

        return view('supply.forecasting.exclusions.show', ['rule' => $rule]);
    }

    public function edit(SalesExclusionRule $rule): View
    {
        Gate::authorize('update', $rule);

        $rule->load(['company:id,name', 'supplier:id,name,code', 'product:id,sku,name,category']);

        return view('supply.forecasting.exclusions.create', $this->formData() + ['rule' => $rule]);
    }

    public function update(StoreSalesExclusionRuleRequest $request, SalesExclusionRule $rule, SalesExclusionService $service): RedirectResponse
    {
        $service->updateRule($rule, $request->validated(), $request->user());

        return redirect()->route('supply.forecasting.exclusions.show', $rule)->with('status', 'Sales exclusion rule updated.');
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
            'ruleTypes' => array_map(fn (SalesExclusionRuleType $type): string => $type->value, SalesExclusionRuleType::cases()),
            'appliesTo' => ['trend_period', 't0_t1', 't1_t2', 't2_t3', 'all_calculation_periods'],
        ];
    }
}
