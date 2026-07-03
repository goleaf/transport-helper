<?php

namespace App\Services\Supply\Incidents;

use App\Enums\CorrectiveActionStatus;
use App\Enums\IncidentSeverity;
use App\Models\IncidentCorrectiveAction;
use App\Models\OperationalIncident;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Validation\ValidationException;

class IncidentCorrectiveActionService
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public function createAction(OperationalIncident $incident, array $validated, User $user): array
    {
        $this->validateDueDate($incident, $validated);
        $action = $incident->correctiveActions()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'owner_user_id' => $validated['owner_user_id'] ?? null,
            'due_date' => $validated['due_date'] ?? null,
            'status' => $validated['status'] ?? CorrectiveActionStatus::Open->value,
        ]);

        $this->auditLogService->write('incident_corrective_action_created', $incident, $user, null, null, [
            'corrective_action_id' => $action->id,
        ], $incident->company_id);

        return ['action' => $action, 'incident' => $incident->fresh()];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public function updateAction(IncidentCorrectiveAction $action, array $validated, User $user): array
    {
        $oldValues = $action->only(['title', 'description', 'owner_user_id', 'due_date', 'status', 'completion_note']);
        $action->update(array_intersect_key($validated, array_flip(array_keys($oldValues))));
        $this->auditLogService->write('incident_corrective_action_updated', $action->incident, $user, $oldValues, $action->fresh()->only(array_keys($oldValues)), [
            'corrective_action_id' => $action->id,
        ], $action->incident?->company_id);

        return ['action' => $action->fresh()];
    }

    /**
     * @return array<string, mixed>
     */
    public function markDone(IncidentCorrectiveAction $action, User $user, string $completionNote): array
    {
        if (trim($completionNote) === '') {
            throw ValidationException::withMessages(['completion_note' => 'Completion note is required.']);
        }

        $action->forceFill([
            'status' => CorrectiveActionStatus::Done->value,
            'completion_note' => $completionNote,
            'completed_at' => now(),
        ])->save();

        $this->auditLogService->write('incident_corrective_action_done', $action->incident, $user, null, null, [
            'corrective_action_id' => $action->id,
        ], $action->incident?->company_id);

        return ['action' => $action->fresh()];
    }

    /**
     * @return array<string, mixed>
     */
    public function verify(IncidentCorrectiveAction $action, User $user, ?string $note = null): array
    {
        $action->forceFill([
            'status' => CorrectiveActionStatus::Verified->value,
            'verified_by_user_id' => $user->id,
            'verified_at' => now(),
        ])->save();

        $this->auditLogService->write('incident_corrective_action_verified', $action->incident, $user, null, null, [
            'corrective_action_id' => $action->id,
            'note' => $note,
        ], $action->incident?->company_id);

        return ['action' => $action->fresh()];
    }

    /**
     * @return array<string, mixed>
     */
    public function cancel(IncidentCorrectiveAction $action, User $user, string $reason): array
    {
        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'Cancellation reason is required.']);
        }

        $action->forceFill(['status' => CorrectiveActionStatus::Cancelled->value])->save();
        $this->auditLogService->write('incident_corrective_action_updated', $action->incident, $user, null, null, [
            'corrective_action_id' => $action->id,
            'reason' => $reason,
        ], $action->incident?->company_id);

        return ['action' => $action->fresh()];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function validateDueDate(OperationalIncident $incident, array $validated): void
    {
        $severity = $incident->severity instanceof IncidentSeverity ? $incident->severity->value : (string) $incident->severity;
        if (in_array($severity, [IncidentSeverity::Critical->value, IncidentSeverity::High->value], true) && empty($validated['due_date'])) {
            throw ValidationException::withMessages(['due_date' => 'Due date is required for critical and high incidents.']);
        }
    }
}
