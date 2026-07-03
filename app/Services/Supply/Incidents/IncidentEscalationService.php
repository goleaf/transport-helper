<?php

namespace App\Services\Supply\Incidents;

use App\Enums\EscalationStatus;
use App\Enums\IncidentPriority;
use App\Enums\IncidentSlaStatus;
use App\Enums\UserRole;
use App\Models\OperationalIncident;
use App\Models\User;
use App\Services\Audit\AuditLogService;

class IncidentEscalationService
{
    public function __construct(
        private readonly IncidentSlaService $slaService,
        private readonly IncidentNotificationService $notificationService,
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function escalate(OperationalIncident $incident, string $reason, ?User $user = null, array $options = []): array
    {
        $latestLevel = (int) $incident->escalations()->max('escalation_level');
        $escalation = $incident->escalations()->create([
            'escalation_level' => $latestLevel + 1,
            'escalated_to_user_id' => $options['escalated_to_user_id'] ?? $this->manager()?->id,
            'escalated_by_user_id' => $user?->id,
            'reason' => $reason,
            'status' => EscalationStatus::Open->value,
            'escalated_at' => now(),
            'metadata_json' => $options['metadata_json'] ?? [],
        ]);

        $incident->events()->create([
            'event_type' => 'incident_escalated',
            'new_values_json' => ['incident_escalation_id' => $escalation->id],
            'metadata_json' => ['reason' => $reason],
            'created_by_user_id' => $user?->id,
            'created_at' => now(),
        ]);
        $this->auditLogService->write('incident_escalated', $incident, $user, null, null, [
            'reason' => $reason,
            'escalation_level' => $escalation->escalation_level,
        ], $incident->company_id);
        $this->notificationService->notify($incident, 'escalated', ['user' => $user]);

        return ['incident' => $incident->fresh(), 'escalation' => $escalation];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function monitorEscalations(array $options = []): array
    {
        $dryRun = (bool) ($options['dry_run'] ?? false);
        $candidates = OperationalIncident::query()
            ->select(['id', 'company_id', 'incident_type', 'severity', 'priority', 'status', 'sla_status', 'first_response_at', 'response_due_at', 'resolution_due_at', 'resolved_at', 'created_at'])
            ->active()
            ->when(isset($options['company_id']), fn ($query) => $query->where('company_id', $options['company_id']))
            ->orderBy('id')
            ->limit(1000)
            ->get();

        $created = 0;
        $findings = [];
        foreach ($candidates as $incident) {
            $sla = $this->slaService->evaluate($incident, ! $dryRun);
            $shouldEscalate = $this->enumValue($incident->priority) === IncidentPriority::P1->value
                || in_array($sla['sla_status'], [IncidentSlaStatus::ResponseBreached->value, IncidentSlaStatus::ResolutionBreached->value], true);

            if (! $shouldEscalate) {
                continue;
            }

            $findings[] = ['incident_id' => $incident->id, 'reason' => $sla['sla_status']];
            if (! $dryRun) {
                $this->escalate($incident, 'SLA or priority escalation: '.$sla['sla_status']);
                $created++;
            }
        }

        return [
            'checked_count' => $candidates->count(),
            'escalations_created' => $created,
            'dry_run' => $dryRun,
            'findings' => $findings,
        ];
    }

    private function manager(): ?User
    {
        return User::query()
            ->select(['id', 'name', 'email', 'role'])
            ->where('role', UserRole::Admin->value)
            ->orderBy('id')
            ->first();
    }

    private function enumValue(mixed $value): string
    {
        return $value instanceof \BackedEnum ? $value->value : (string) $value;
    }
}
