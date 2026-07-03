<?php

namespace App\Http\Controllers\Supply;

use App\Enums\IncidentPriority;
use App\Enums\IncidentSeverity;
use App\Enums\IncidentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\StoreIncidentSlaPolicyRequest;
use App\Models\Company;
use App\Models\IncidentSlaPolicy;
use App\Services\Audit\AuditLogService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class IncidentSlaPolicyController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', IncidentSlaPolicy::class);

        return view('supply.incidents.sla-policies.index', [
            'policies' => IncidentSlaPolicy::query()
                ->select(['id', 'company_id', 'name', 'incident_type', 'severity', 'priority', 'response_minutes', 'resolution_minutes', 'escalation_minutes', 'is_active', 'created_at'])
                ->with(['company:id,name'])
                ->orderByDesc('id')
                ->paginate(25),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', IncidentSlaPolicy::class);

        return view('supply.incidents.sla-policies.create', [
            'companies' => Company::query()->select(['id', 'name'])->orderBy('name')->limit(200)->get(),
            'types' => IncidentType::values(),
            'severities' => IncidentSeverity::values(),
            'priorities' => IncidentPriority::values(),
        ]);
    }

    public function store(StoreIncidentSlaPolicyRequest $request, AuditLogService $auditLogService): RedirectResponse
    {
        $policy = IncidentSlaPolicy::query()->create($request->validated() + [
            'created_by_user_id' => $request->user()?->id,
            'is_active' => (bool) $request->boolean('is_active', true),
        ]);

        $auditLogService->write('incident_sla_policy_created', $policy, $request->user(), null, [
            'name' => $policy->name,
            'incident_type' => $policy->incident_type,
            'severity' => $policy->severity,
            'priority' => $policy->priority,
        ], [], $policy->company_id);

        return redirect()->route('supply.incidents.sla-policies.index')->with('status', 'Incident SLA policy created.');
    }
}
