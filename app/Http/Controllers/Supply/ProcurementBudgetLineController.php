<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\StoreProcurementBudgetLineRequest;
use App\Models\ProcurementBudget;
use App\Services\Supply\Procurement\BudgetService;
use Illuminate\Http\RedirectResponse;

class ProcurementBudgetLineController extends Controller
{
    public function store(StoreProcurementBudgetLineRequest $request, ProcurementBudget $budget, BudgetService $service): RedirectResponse
    {
        $result = $service->addLine($budget, $request->validated(), $request->user());
        $message = $result['warnings'] === [] ? 'Budget line added.' : 'Budget line added with warnings.';

        return redirect()->route('supply.procurement.budgets.show', $budget)->with('status', $message);
    }
}
