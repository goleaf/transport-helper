<?php

namespace App\Services\Supply\Incidents;

use App\Models\OperationalIncident;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Validation\ValidationException;

class IncidentRootCauseService
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public function setRootCause(OperationalIncident $incident, array $validated, User $user): array
    {
        if (trim((string) ($validated['root_cause_category'] ?? '')) === '') {
            throw ValidationException::withMessages(['root_cause_category' => 'Root cause category is required.']);
        }

        if (trim((string) ($validated['root_cause_summary'] ?? '')) === '') {
            throw ValidationException::withMessages(['root_cause_summary' => 'Root cause summary is required.']);
        }

        $oldValues = $incident->only(['root_cause_category', 'root_cause_summary', 'prevention_notes', 'corrective_action_required', 'no_action_required_reason']);
        $incident->forceFill([
            'root_cause_category' => $validated['root_cause_category'],
            'root_cause_summary' => $validated['root_cause_summary'],
            'prevention_notes' => $validated['prevention_notes'] ?? null,
            'corrective_action_required' => (bool) ($validated['corrective_action_required'] ?? false),
            'no_action_required_reason' => $validated['no_action_required_reason'] ?? null,
        ])->save();

        $incident->events()->create([
            'event_type' => 'incident_root_cause_updated',
            'old_values_json' => $oldValues,
            'new_values_json' => $incident->fresh()->only(array_keys($oldValues)),
            'created_by_user_id' => $user->id,
            'created_at' => now(),
        ]);
        $this->auditLogService->write('incident_root_cause_updated', $incident, $user, $oldValues, $incident->fresh()->only(array_keys($oldValues)), [], $incident->company_id);

        return ['incident' => $incident->fresh()];
    }
}
