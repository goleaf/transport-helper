<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\AddIncidentCommentRequest;
use App\Models\OperationalIncident;
use App\Services\Supply\Incidents\IncidentUpdateService;
use Illuminate\Http\RedirectResponse;

class IncidentCommentController extends Controller
{
    public function store(AddIncidentCommentRequest $request, OperationalIncident $incident, IncidentUpdateService $service): RedirectResponse
    {
        $service->addComment($incident, $request->validated('comment'), $request->user(), $request->validated());

        return redirect()->route('supply.incidents.show', $incident)->with('status', 'Incident comment added.');
    }
}
