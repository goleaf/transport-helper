<?php

namespace App\Services\Supply\Incidents;

use App\Enums\IncidentType;
use App\Enums\UserRole;
use App\Models\OperationalIncident;
use App\Models\User;
use App\Services\Audit\AuditLogService;

class IncidentAssignmentService
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    /**
     * @return array<string, mixed>
     */
    public function assign(OperationalIncident $incident, User $assignee, User $assignedBy, ?string $reason = null): array
    {
        $oldValues = ['assigned_user_id' => $incident->assigned_user_id];
        $incident->forceFill([
            'assigned_user_id' => $assignee->id,
            'first_response_at' => $incident->first_response_at ?? now(),
        ])->save();

        $incident->events()->create([
            'event_type' => 'incident_assigned',
            'old_values_json' => $oldValues,
            'new_values_json' => ['assigned_user_id' => $assignee->id],
            'metadata_json' => ['reason' => $reason],
            'created_by_user_id' => $assignedBy->id,
            'created_at' => now(),
        ]);
        $this->auditLogService->write('incident_assigned', $incident, $assignedBy, $oldValues, ['assigned_user_id' => $assignee->id], [
            'reason' => $reason,
        ], $incident->company_id);

        return ['incident' => $incident->fresh()];
    }

    /**
     * @return array<string, mixed>
     */
    public function autoAssign(OperationalIncident $incident): array
    {
        $assignee = $this->candidateAssignees($incident)[0] ?? null;

        if (! $assignee instanceof User) {
            return ['incident' => $incident, 'assigned' => false];
        }

        $incident->forceFill(['assigned_user_id' => $assignee->id])->save();
        $incident->events()->create([
            'event_type' => 'incident_assigned',
            'new_values_json' => ['assigned_user_id' => $assignee->id],
            'metadata_json' => ['auto_assigned' => true],
            'created_at' => now(),
        ]);
        $this->auditLogService->write('incident_assigned', $incident, null, null, ['assigned_user_id' => $assignee->id], [
            'auto_assigned' => true,
        ], $incident->company_id);

        return ['incident' => $incident->fresh(), 'assigned' => true];
    }

    /**
     * @return list<User>
     */
    public function candidateAssignees(OperationalIncident $incident): array
    {
        $roles = $this->rolesForIncident($incident);

        return User::query()
            ->select(['id', 'name', 'email', 'role'])
            ->whereIn('role', $roles)
            ->orderBy('id')
            ->limit(10)
            ->get()
            ->all();
    }

    /**
     * @return list<string>
     */
    private function rolesForIncident(OperationalIncident $incident): array
    {
        $type = $incident->incident_type instanceof IncidentType ? $incident->incident_type->value : (string) $incident->incident_type;

        return match ($type) {
            IncidentType::LogisticsDelay->value,
            IncidentType::CarrierQuoteNeedsReview->value,
            IncidentType::CarrierSelectionBlocked->value,
            IncidentType::ReceivingMismatch->value => [UserRole::LogisticsManager->value, UserRole::SupplyManager->value, UserRole::Admin->value],
            IncidentType::UnknownSkuUnresolved->value,
            IncidentType::MasterDataDuplicate->value => [UserRole::SupplyManager->value, UserRole::Admin->value],
            default => [UserRole::SupplyManager->value, UserRole::Admin->value],
        };
    }
}
