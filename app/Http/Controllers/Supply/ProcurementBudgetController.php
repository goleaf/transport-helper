<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\StoreProcurementBudgetRequest;
use App\Http\Requests\Supply\UpdateProcurementBudgetRequest;
use App\Models\Company;
use App\Models\ProcurementBudget;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Supply\Procurement\BudgetService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ProcurementBudgetController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', ProcurementBudget::class);

        return view('supply.procurement.budgets.index', [
            'budgets' => ProcurementBudget::query()
                ->select(['id', 'company_id', 'name', 'period_type', 'date_from', 'date_to', 'currency', 'total_amount', 'status', 'owner_user_id', 'created_by_user_id'])
                ->with(['company:id,name', 'owner:id,name', 'createdBy:id,name'])
                ->withCount('lines')
                ->latest('date_from')
                ->paginate(25)
                ->withQueryString(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', ProcurementBudget::class);

        return view('supply.procurement.budgets.create', $this->formData());
    }

    public function store(StoreProcurementBudgetRequest $request, BudgetService $service): RedirectResponse
    {
        $result = $service->createBudget($request->validated(), $request->user());

        return redirect()->route('supply.procurement.budgets.show', $result['budget'])->with('status', 'Procurement budget created.');
    }

    public function show(ProcurementBudget $budget): View
    {
        Gate::authorize('view', $budget);

        $budget->load([
            'company:id,name',
            'owner:id,name',
            'createdBy:id,name',
            'lines.supplier:id,name',
            'lines.product:id,sku,name,category',
        ]);

        return view('supply.procurement.budgets.show', $this->lineFormData() + ['budget' => $budget]);
    }

    public function edit(ProcurementBudget $budget): View
    {
        Gate::authorize('update', $budget);

        return view('supply.procurement.budgets.edit', $this->formData() + ['budget' => $budget]);
    }

    public function update(UpdateProcurementBudgetRequest $request, ProcurementBudget $budget, BudgetService $service): RedirectResponse
    {
        $service->updateBudget($budget, $request->validated(), $request->user());

        return redirect()->route('supply.procurement.budgets.show', $budget)->with('status', 'Procurement budget updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'companies' => Company::query()->select(['id', 'name'])->orderBy('name')->limit(200)->get(),
            'users' => User::query()->select(['id', 'name'])->orderBy('name')->limit(200)->get(),
            'periodTypes' => ['monthly', 'quarterly', 'yearly', 'custom'],
            'statuses' => ['draft', 'active', 'closed', 'archived'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function lineFormData(): array
    {
        return [
            'suppliers' => Supplier::query()->select(['id', 'name'])->orderBy('name')->limit(300)->get(),
            'products' => Product::query()->select(['id', 'sku', 'name', 'category'])->orderBy('sku')->limit(500)->get(),
        ];
    }
}
