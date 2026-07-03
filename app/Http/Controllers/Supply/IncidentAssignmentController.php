<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\AssignIncidentRequest;
use App\Models\OperationalIncident;
use App\Models\User;
use App\Services\Supply\Incidents\IncidentAssignmentService;
use Illuminate\Http\RedirectResponse;

class IncidentAssignmentController extends Controller
{
    public function store(AssignIncidentRequest $request, OperationalIncident $incident, IncidentAssignmentService $service): RedirectResponse
    {
        $assignee = User::query()->select(['id', 'name', 'email', 'role'])->findOrFail((int) $request->validated('assigned_user_id'));
        $service->assign($incident, $assignee, $request->user(), $request->validated('reason'));

        return redirect()->route('supply.incidents.show', $incident)->with('status', 'Incident assigned.');
    }
}
