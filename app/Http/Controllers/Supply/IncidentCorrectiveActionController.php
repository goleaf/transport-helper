<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\StoreIncidentCorrectiveActionRequest;
use App\Http\Requests\Supply\UpdateIncidentCorrectiveActionRequest;
use App\Models\IncidentCorrectiveAction;
use App\Models\OperationalIncident;
use App\Services\Supply\Incidents\IncidentCorrectiveActionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class IncidentCorrectiveActionController extends Controller
{
    public function store(StoreIncidentCorrectiveActionRequest $request, OperationalIncident $incident, IncidentCorrectiveActionService $service): RedirectResponse
    {
        $service->createAction($incident, $request->validated(), $request->user());

        return redirect()->route('supply.incidents.show', $incident)->with('status', 'Corrective action created.');
    }

    public function update(UpdateIncidentCorrectiveActionRequest $request, OperationalIncident $incident, IncidentCorrectiveAction $action, IncidentCorrectiveActionService $service): RedirectResponse
    {
        abort_unless($action->operational_incident_id === $incident->id, 404);

        $service->updateAction($action, $request->validated(), $request->user());

        return redirect()->route('supply.incidents.show', $incident)->with('status', 'Corrective action updated.');
    }

    public function done(Request $request, OperationalIncident $incident, IncidentCorrectiveAction $action, IncidentCorrectiveActionService $service): RedirectResponse
    {
        abort_unless($action->operational_incident_id === $incident->id, 404);

        Gate::authorize('markDone', $action);
        $request->validate(['completion_note' => ['required', 'string', 'max:10000']]);
        $service->markDone($action, $request->user(), (string) $request->input('completion_note'));

        return redirect()->route('supply.incidents.show', $incident)->with('status', 'Corrective action completed.');
    }

    public function verify(Request $request, OperationalIncident $incident, IncidentCorrectiveAction $action, IncidentCorrectiveActionService $service): RedirectResponse
    {
        abort_unless($action->operational_incident_id === $incident->id, 404);

        Gate::authorize('verify', $action);
        $service->verify($action, $request->user(), $request->input('note'));

        return redirect()->route('supply.incidents.show', $incident)->with('status', 'Corrective action verified.');
    }
}
