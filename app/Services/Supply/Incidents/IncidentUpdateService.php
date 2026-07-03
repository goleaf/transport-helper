<?php

namespace App\Services\Supply\Incidents;

use App\Enums\IncidentSeverity;
use App\Enums\IncidentStatus;
use App\Models\OperationalIncident;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Validation\ValidationException;

class IncidentUpdateService
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public function update(OperationalIncident $incident, array $validated, User $user): array
    {
        $oldValues = $incident->only(['title', 'description', 'severity', 'priority', 'assigned_user_id', 'metadata_json']);
        $incident->update(array_intersect_key($validated, array_flip([
            'title',
            'description',
            'severity',
            'priority',
            'assigned_user_id',
            'metadata_json',
        ])));

        $this->recordEvent($incident, 'incident_updated', $oldValues, $incident->fresh()->only(array_keys($oldValues)), $user);
        $this->auditLogService->write('operational_incident_updated', $incident, $user, $oldValues, $incident->fresh()->only(array_keys($oldValues)), [], $incident->company_id);

        return ['incident' => $incident->fresh()];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function changeStatus(OperationalIncident $incident, string $status, User $user, array $data = []): array
    {
        $oldStatus = $this->enumValue($incident->status);
        $this->validateTransition($oldStatus, $status, $incident, $data);

        $updates = ['status' => $status];
        if ($status === IncidentStatus::Resolved->value) {
            $updates['resolution_note'] = $data['resolution_note'];
            $updates['resolved_at'] = now();
        }
        if ($status === IncidentStatus::Closed->value) {
            $updates['closed_at'] = now();
        }

        $incident->forceFill($updates)->save();
        $event = match ($status) {
            IncidentStatus::Resolved->value => 'incident_resolved',
            IncidentStatus::Closed->value => 'incident_closed',
            IncidentStatus::Cancelled->value => 'incident_cancelled',
            default => 'incident_status_changed',
        };

        $this->recordEvent($incident, $event, ['status' => $oldStatus], ['status' => $status], $user, $data);
        $this->auditLogService->write($event, $incident, $user, ['status' => $oldStatus], ['status' => $status], $data, $incident->company_id);

        return ['incident' => $incident->fresh()];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function addComment(OperationalIncident $incident, string $comment, User $user, array $data = []): array
    {
        if (trim($comment) === '') {
            throw ValidationException::withMessages(['comment' => 'Comment is required.']);
        }

        $created = $incident->comments()->create([
            'user_id' => $user->id,
            'comment' => $comment,
            'is_internal' => (bool) ($data['is_internal'] ?? true),
            'metadata_json' => $data['metadata_json'] ?? [],
        ]);
        $this->recordEvent($incident, 'incident_comment_added', null, ['comment_id' => $created->id], $user);
        $this->auditLogService->write('incident_comment_added', $incident, $user, null, null, ['comment_id' => $created->id], $incident->company_id);

        return ['comment' => $created, 'incident' => $incident->fresh()];
    }

    /**
     * @param  array<string, mixed>|null  $old
     * @param  array<string, mixed>|null  $new
     * @param  array<string, mixed>  $metadata
     */
    private function recordEvent(OperationalIncident $incident, string $eventType, ?array $old, ?array $new, User $user, array $metadata = []): void
    {
        $incident->events()->create([
            'event_type' => $eventType,
            'old_values_json' => $old,
            'new_values_json' => $new,
            'metadata_json' => $metadata,
            'created_by_user_id' => $user->id,
            'created_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function validateTransition(string $oldStatus, string $newStatus, OperationalIncident $incident, array $data): void
    {
        if (in_array($oldStatus, [IncidentStatus::Closed->value, IncidentStatus::Cancelled->value], true)) {
            throw ValidationException::withMessages(['status' => 'Closed or cancelled incidents are terminal.']);
        }

        if ($newStatus === IncidentStatus::Resolved->value && trim((string) ($data['resolution_note'] ?? '')) === '') {
            throw ValidationException::withMessages(['resolution_note' => 'Resolution note is required before resolving an incident.']);
        }

        if ($newStatus === IncidentStatus::Closed->value && in_array($this->enumValue($incident->severity), [IncidentSeverity::Critical->value, IncidentSeverity::High->value], true)) {
            $hasRca = $incident->root_cause_category !== null && $incident->root_cause_summary !== null;
            $hasAction = $incident->correctiveActions()->exists();
            $hasNoActionReason = trim((string) ($incident->no_action_required_reason ?? $data['no_action_required_reason'] ?? '')) !== '';

            if (! $hasRca || (! $hasAction && ! $hasNoActionReason)) {
                throw ValidationException::withMessages(['root_cause' => 'Critical and high incidents require root cause and corrective action or no-action reason before closing.']);
            }
        }
    }

    private function enumValue(mixed $value): string
    {
        return $value instanceof \BackedEnum ? $value->value : (string) $value;
    }
}
