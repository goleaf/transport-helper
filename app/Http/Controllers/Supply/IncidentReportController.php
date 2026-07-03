<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Models\OperationalIncident;
use App\Services\Supply\Incidents\IncidentReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class IncidentReportController extends Controller
{
    public function index(Request $request, IncidentReportService $service): View
    {
        Gate::authorize('viewAny', OperationalIncident::class);

        return view('supply.incidents.reports.index', [
            'report' => $service->report($request->only(['company_id', 'date_from', 'date_to', 'status', 'severity', 'type', 'assigned_user_id', 'sla_status', 'source_type'])),
        ]);
    }
}
