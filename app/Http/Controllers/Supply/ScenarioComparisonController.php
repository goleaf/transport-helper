<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\CompareScenariosRequest;
use App\Models\CalculationScenario;
use App\Services\Audit\AuditLogService;
use App\Services\Supply\Forecasting\ScenarioComparisonService;
use Illuminate\Contracts\View\View;

class ScenarioComparisonController extends Controller
{
    public function store(CompareScenariosRequest $request, ScenarioComparisonService $service, AuditLogService $auditLogService): View
    {
        $validated = $request->validated();
        $scenarioA = CalculationScenario::query()->findOrFail($validated['scenario_a_id']);
        $scenarioB = CalculationScenario::query()->findOrFail($validated['scenario_b_id']);
        $comparison = $service->compare($scenarioA, $scenarioB);

        $auditLogService->write('scenario_compared', $scenarioB, $request->user(), null, [
            'scenario_a_id' => $scenarioA->getKey(),
            'scenario_b_id' => $scenarioB->getKey(),
            'summary' => $comparison['summary'],
        ], [], $scenarioB->company_id);

        return view('supply.forecasting.scenarios.compare', [
            'scenarioA' => $scenarioA,
            'scenarioB' => $scenarioB,
            'comparison' => $comparison,
        ]);
    }
}
