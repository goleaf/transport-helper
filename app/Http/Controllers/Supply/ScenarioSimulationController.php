<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\RunScenarioSimulationRequest;
use App\Models\Company;
use App\Models\Supplier;
use App\Services\Supply\Forecasting\ScenarioSimulationService;
use Illuminate\Http\RedirectResponse;

class ScenarioSimulationController extends Controller
{
    public function store(RunScenarioSimulationRequest $request, ScenarioSimulationService $service): RedirectResponse
    {
        $validated = $request->validated();
        $company = Company::query()->select(['id', 'name'])->findOrFail($validated['company_id']);
        $supplier = Supplier::query()->select(['id', 'company_id', 'name', 'code', 'default_lead_time_days'])->findOrFail($validated['supplier_id']);
        $result = $service->simulate($company, $supplier, $validated, $request->user());

        return redirect()->route('supply.forecasting.scenarios.show', $result['scenario'])->with('status', 'Scenario simulated. Review is still required before any order action.');
    }
}
