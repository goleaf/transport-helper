<?php

namespace App\Services\Supply\Incidents;

use App\Enums\IncidentSlaStatus;
use App\Enums\IncidentStatus;
use App\Models\IncidentSlaPolicy;
use App\Models\OperationalIncident;
use App\Services\Audit\AuditLogService;
use Illuminate\Support\Carbon;

class IncidentSlaService
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    /**
     * @return array{response_minutes:int,resolution_minutes:int,escalation_minutes:?int,source:string}
     */
    public function policyFor(string $incidentType, string $severity, string $priority, ?int $companyId = null): array
    {
        $policy = IncidentSlaPolicy::query()
            ->select(['id', 'company_id', 'incident_type', 'severity', 'priority', 'response_minutes', 'resolution_minutes', 'escalation_minutes', 'is_active'])
            ->where('is_active', true)
            ->where(function ($query) use ($companyId): void {
                $query->whereNull('company_id');
                if ($companyId !== null) {
                    $query->orWhere('company_id', $companyId);
                }
            })
            ->where(function ($query) use ($incidentType): void {
                $query->whereNull('incident_type')->orWhere('incident_type', $incidentType);
            })
            ->where(function ($query) use ($severity): void {
                $query->whereNull('severity')->orWhere('severity', $severity);
            })
            ->where(function ($query) use ($priority): void {
                $query->whereNull('priority')->orWhere('priority', $priority);
            })
            ->get()
            ->sortByDesc(fn (IncidentSlaPolicy $policy): int => $this->specificity($policy, $companyId))
            ->first();

        if ($policy instanceof IncidentSlaPolicy) {
            return [
                'response_minutes' => (int) $policy->response_minutes,
                'resolution_minutes' => (int) $policy->resolution_minutes,
                'escalation_minutes' => $policy->escalation_minutes === null ? null : (int) $policy->escalation_minutes,
                'source' => 'policy',
            ];
        }

        return [
            'response_minutes' => (int) config("supply.incidents.default_response_minutes.{$severity}", 4320),
            'resolution_minutes' => (int) config("supply.incidents.default_resolution_minutes.{$severity}", 14400),
            'escalation_minutes' => null,
            'source' => 'default',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function assignDueDates(OperationalIncident $incident): array
    {
        $policy = $this->policyFor(
            $this->enumValue($incident->incident_type),
            $this->enumValue($incident->severity),
            $this->enumValue($incident->priority),
            $incident->company_id,
        );
        $start = $incident->created_at instanceof Carbon ? $incident->created_at : now();

        $incident->forceFill([
            'response_due_at' => $start->copy()->addMinutes($policy['response_minutes']),
            'resolution_due_at' => $start->copy()->addMinutes($policy['resolution_minutes']),
            'sla_status' => IncidentSlaStatus::WithinSla->value,
        ])->save();

        return ['incident' => $incident->fresh(), 'policy' => $policy];
    }

    /**
     * @return array<string, mixed>
     */
    public function evaluate(OperationalIncident $incident, bool $update = false): array
    {
        $now = now();
        $resolved = $incident->resolved_at !== null || in_array($this->enumValue($incident->status), [IncidentStatus::Resolved->value, IncidentStatus::Closed->value], true);
        $status = IncidentSlaStatus::WithinSla->value;

        if ($resolved) {
            $status = $incident->resolution_due_at !== null && $incident->resolved_at !== null && $incident->resolved_at->greaterThan($incident->resolution_due_at)
                ? IncidentSlaStatus::CompletedBreached->value
                : IncidentSlaStatus::CompletedWithinSla->value;
        } elseif ($incident->resolution_due_at !== null && $now->greaterThan($incident->resolution_due_at)) {
            $status = IncidentSlaStatus::ResolutionBreached->value;
        } elseif ($incident->first_response_at === null && $incident->response_due_at !== null && $now->greaterThan($incident->response_due_at)) {
            $status = IncidentSlaStatus::ResponseBreached->value;
        }

        if ($update && $this->enumValue($incident->sla_status) !== $status) {
            $oldStatus = $this->enumValue($incident->sla_status);
            $incident->forceFill(['sla_status' => $status])->save();

            if (in_array($status, [IncidentSlaStatus::ResponseBreached->value, IncidentSlaStatus::ResolutionBreached->value], true)) {
                $this->auditLogService->write('incident_sla_breached', $incident, null, ['sla_status' => $oldStatus], ['sla_status' => $status], [], $incident->company_id);
            }
        }

        return [
            'incident_id' => $incident->id,
            'sla_status' => $status,
            'response_due_at' => $incident->response_due_at?->toISOString(),
            'resolution_due_at' => $incident->resolution_due_at?->toISOString(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function monitor(array $filters = []): array
    {
        $dryRun = (bool) ($filters['dry_run'] ?? false);
        $query = OperationalIncident::query()
            ->select(['id', 'company_id', 'incident_type', 'severity', 'priority', 'status', 'first_response_at', 'response_due_at', 'resolution_due_at', 'resolved_at', 'sla_status', 'created_at'])
            ->active()
            ->when(isset($filters['company_id']), fn ($query) => $query->where('company_id', $filters['company_id']))
            ->orderBy('id')
            ->limit(1000);

        $breaches = [];
        foreach ($query->get() as $incident) {
            $result = $this->evaluate($incident, ! $dryRun);
            if (in_array($result['sla_status'], [IncidentSlaStatus::ResponseBreached->value, IncidentSlaStatus::ResolutionBreached->value], true)) {
                $breaches[] = $result;
            }
        }

        return [
            'checked_count' => $query->count(),
            'breach_count' => count($breaches),
            'dry_run' => $dryRun,
            'breaches' => $breaches,
        ];
    }

    private function specificity(IncidentSlaPolicy $policy, ?int $companyId): int
    {
        return (int) ($policy->company_id === $companyId)
            + (int) ($policy->incident_type !== null)
            + (int) ($policy->severity !== null)
            + (int) ($policy->priority !== null);
    }

    private function enumValue(mixed $value): string
    {
        return $value instanceof \BackedEnum ? $value->value : (string) $value;
    }
}
