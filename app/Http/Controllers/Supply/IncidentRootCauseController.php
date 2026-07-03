<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\ResolveIncidentRootCauseRequest;
use App\Models\OperationalIncident;
use App\Services\Supply\Incidents\IncidentRootCauseService;
use Illuminate\Http\RedirectResponse;

class IncidentRootCauseController extends Controller
{
    public function store(ResolveIncidentRootCauseRequest $request, OperationalIncident $incident, IncidentRootCauseService $service): RedirectResponse
    {
        $service->setRootCause($incident, $request->validated(), $request->user());

        return redirect()->route('supply.incidents.show', $incident)->with('status', 'Root cause updated.');
    }
}
