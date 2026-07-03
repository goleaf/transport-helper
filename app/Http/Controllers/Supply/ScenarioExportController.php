<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\ExportScenarioRequest;
use App\Models\CalculationScenario;
use App\Services\Supply\Forecasting\ScenarioExportService;
use Illuminate\Http\RedirectResponse;

class ScenarioExportController extends Controller
{
    public function store(ExportScenarioRequest $request, CalculationScenario $scenario, ScenarioExportService $service): RedirectResponse
    {
        $format = $request->validated('format');
        $result = $format === 'csv'
            ? $service->exportCsv($scenario, $request->user())
            : $service->exportJson($scenario, $request->user());

        return redirect()->route('supply.forecasting.scenarios.show', $scenario)->with('status', 'Scenario export stored: '.$result['export']->filename);
    }
}
