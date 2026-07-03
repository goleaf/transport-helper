<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\RunIncidentDetectionRequest;
use App\Services\Supply\Incidents\IncidentAutoDetectionService;
use Illuminate\Http\RedirectResponse;

class IncidentDetectionController extends Controller
{
    public function store(RunIncidentDetectionRequest $request, IncidentAutoDetectionService $service): RedirectResponse
    {
        $result = $service->detect($request->validated() + ['dry_run' => $request->boolean('dry_run', true)]);

        return redirect()
            ->route('supply.incidents.index')
            ->with('status', 'Incident detection completed: '.$result['findings_count'].' findings, '.$result['incidents_created'].' created.');
    }
}
