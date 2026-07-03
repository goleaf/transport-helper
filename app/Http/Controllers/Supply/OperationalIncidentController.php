<?php

namespace App\Http\Controllers\Supply;

use App\Enums\IncidentPriority;
use App\Enums\IncidentSeverity;
use App\Enums\IncidentSlaStatus;
use App\Enums\IncidentSourceType;
use App\Enums\IncidentStatus;
use App\Enums\IncidentType;
use App\Enums\RootCauseCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\StoreIncidentRequest;
use App\Http\Requests\Supply\UpdateIncidentRequest;
use App\Models\Company;
use App\Models\OperationalIncident;
use App\Models\User;
use App\Services\Supply\Incidents\IncidentCreationService;
use App\Services\Supply\Incidents\IncidentReportService;
use App\Services\Supply\Incidents\IncidentUpdateService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OperationalIncidentController extends Controller
{
    public function index(Request $request, IncidentReportService $reportService): View
    {
        Gate::authorize('viewAny', OperationalIncident::class);

        $filters = $request->only(['status', 'severity', 'priority', 'type', 'sla_status', 'assigned_user_id', 'source_type', 'date_from', 'date_to']);

        return view('supply.incidents.index', [
            'incidents' => OperationalIncident::query()
                ->select(['id', 'company_id', 'incident_number', 'incident_type', 'severity', 'priority', 'status', 'title', 'source_type', 'source_id', 'source_label', 'assigned_user_id', 'response_due_at', 'resolution_due_at', 'sla_status', 'last_seen_at', 'created_at'])
                ->with(['company:id,name', 'assignedUser:id,name'])
                ->withCount(['correctiveActions', 'escalations'])
                ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
                ->when($request->filled('severity'), fn ($query) => $query->where('severity', $request->string('severity')->toString()))
                ->when($request->filled('priority'), fn ($query) => $query->where('priority', $request->string('priority')->toString()))
                ->when($request->filled('type'), fn ($query) => $query->where('incident_type', $request->string('type')->toString()))
                ->when($request->filled('sla_status'), fn ($query) => $query->where('sla_status', $request->string('sla_status')->toString()))
                ->when($request->filled('assigned_user_id'), fn ($query) => $query->where('assigned_user_id', $request->integer('assigned_user_id')))
                ->when($request->filled('source_type'), fn ($query) => $query->where('source_type', $request->string('source_type')->toString()))
                ->orderByDesc('id')
                ->paginate(25)
                ->withQueryString(),
            'report' => $reportService->report($filters),
            'filters' => $filters,
            'statuses' => IncidentStatus::values(),
            'severities' => IncidentSeverity::values(),
            'priorities' => IncidentPriority::values(),
            'types' => IncidentType::values(),
            'slaStatuses' => IncidentSlaStatus::values(),
            'sourceTypes' => IncidentSourceType::values(),
            'users' => User::query()->select(['id', 'name'])->orderBy('name')->limit(200)->get(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', OperationalIncident::class);

        return view('supply.incidents.create', [
            'companies' => Company::query()->select(['id', 'name'])->orderBy('name')->limit(200)->get(),
            'users' => User::query()->select(['id', 'name'])->orderBy('name')->limit(200)->get(),
            'types' => IncidentType::values(),
            'severities' => IncidentSeverity::values(),
            'priorities' => IncidentPriority::values(),
            'sourceTypes' => IncidentSourceType::values(),
            'incident' => null,
        ]);
    }

    public function store(StoreIncidentRequest $request, IncidentCreationService $service): RedirectResponse
    {
        $result = $service->create($request->validated(), $request->user());

        return redirect()
            ->route('supply.incidents.show', $result['incident'])
            ->with('status', 'Incident created.');
    }

    public function show(OperationalIncident $incident): View
    {
        Gate::authorize('view', $incident);

        $incident->load([
            'company:id,name',
            'assignedUser:id,name',
            'reportedBy:id,name',
            'events:id,operational_incident_id,event_type,old_values_json,new_values_json,metadata_json,created_by_user_id,created_at',
            'events.createdBy:id,name',
            'comments:id,operational_incident_id,user_id,comment,is_internal,created_at',
            'comments.user:id,name',
            'correctiveActions:id,operational_incident_id,title,owner_user_id,due_date,status,completed_at,verified_by_user_id,verified_at',
            'correctiveActions.owner:id,name',
            'correctiveActions.verifiedBy:id,name',
            'escalations:id,operational_incident_id,escalation_level,escalated_to_user_id,reason,status,escalated_at',
            'escalations.escalatedTo:id,name',
        ]);

        return view('supply.incidents.show', [
            'incident' => $incident,
            'users' => User::query()->select(['id', 'name'])->orderBy('name')->limit(200)->get(),
            'statuses' => IncidentStatus::values(),
            'rootCauseCategories' => RootCauseCategory::values(),
        ]);
    }

    public function edit(OperationalIncident $incident): View
    {
        Gate::authorize('update', $incident);

        return view('supply.incidents.edit', [
            'incident' => $incident,
            'users' => User::query()->select(['id', 'name'])->orderBy('name')->limit(200)->get(),
            'severities' => IncidentSeverity::values(),
            'priorities' => IncidentPriority::values(),
        ]);
    }

    public function update(UpdateIncidentRequest $request, OperationalIncident $incident, IncidentUpdateService $service): RedirectResponse
    {
        $service->update($incident, $request->validated(), $request->user());

        return redirect()->route('supply.incidents.show', $incident)->with('status', 'Incident updated.');
    }
}
