<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\ChangeIncidentStatusRequest;
use App\Models\OperationalIncident;
use App\Services\Supply\Incidents\IncidentUpdateService;
use Illuminate\Http\RedirectResponse;

class IncidentStatusController extends Controller
{
    public function store(ChangeIncidentStatusRequest $request, OperationalIncident $incident, IncidentUpdateService $service): RedirectResponse
    {
        $service->changeStatus($incident, $request->validated('status'), $request->user(), $request->validated());

        return redirect()->route('supply.incidents.show', $incident)->with('status', 'Incident status updated.');
    }
}
