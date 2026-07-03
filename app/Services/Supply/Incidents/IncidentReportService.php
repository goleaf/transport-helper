<?php

namespace App\Services\Supply\Incidents;

use App\Enums\CorrectiveActionStatus;
use App\Enums\IncidentSlaStatus;
use App\Enums\IncidentStatus;
use App\Models\IncidentCorrectiveAction;
use App\Models\OperationalIncident;

class IncidentReportService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function report(array $filters = []): array
    {
        $incidents = OperationalIncident::query()
            ->select(['id', 'incident_number', 'incident_type', 'severity', 'priority', 'status', 'title', 'assigned_user_id', 'source_type', 'source_id', 'sla_status', 'root_cause_category', 'created_at', 'resolved_at'])
            ->with(['assignedUser:id,name'])
            ->when(isset($filters['company_id']), fn ($query) => $query->where('company_id', $filters['company_id']))
            ->when(isset($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(isset($filters['severity']), fn ($query) => $query->where('severity', $filters['severity']))
            ->when(isset($filters['type']), fn ($query) => $query->where('incident_type', $filters['type']))
            ->when(isset($filters['assigned_user_id']), fn ($query) => $query->where('assigned_user_id', $filters['assigned_user_id']))
            ->when(isset($filters['sla_status']), fn ($query) => $query->where('sla_status', $filters['sla_status']))
            ->when(isset($filters['source_type']), fn ($query) => $query->where('source_type', $filters['source_type']))
            ->when(isset($filters['date_from']), fn ($query) => $query->whereDate('created_at', '>=', $filters['date_from']))
            ->when(isset($filters['date_to']), fn ($query) => $query->whereDate('created_at', '<=', $filters['date_to']))
            ->orderByDesc('id')
            ->limit(5000)
            ->get();

        $correctiveActions = IncidentCorrectiveAction::query()
            ->select(['id', 'status'])
            ->limit(5000)
            ->get();

        return [
            'title' => 'Incident Report',
            'summary' => [
                'total_incidents' => $incidents->count(),
                'open_by_severity' => $incidents
                    ->filter(fn (OperationalIncident $incident): bool => ! in_array($this->enumValue($incident->status), [IncidentStatus::Closed->value, IncidentStatus::Cancelled->value], true))
                    ->groupBy(fn (OperationalIncident $incident): string => $this->enumValue($incident->severity) ?? 'unknown')
                    ->map->count()
                    ->all(),
                'sla_breaches' => $incidents
                    ->filter(fn (OperationalIncident $incident): bool => in_array($this->enumValue($incident->sla_status), [IncidentSlaStatus::ResponseBreached->value, IncidentSlaStatus::ResolutionBreached->value, IncidentSlaStatus::CompletedBreached->value], true))
                    ->count(),
                'average_resolution_hours' => $this->averageResolutionHours($incidents),
                'corrective_action_completion' => [
                    'total' => $correctiveActions->count(),
                    'done_or_verified' => $correctiveActions->whereIn('status', [CorrectiveActionStatus::Done, CorrectiveActionStatus::Verified])->count(),
                ],
            ],
            'by_type' => $incidents->groupBy(fn (OperationalIncident $incident): string => $this->enumValue($incident->incident_type) ?? 'unknown')->map->count()->all(),
            'by_owner' => $incidents->groupBy(fn (OperationalIncident $incident): string => $incident->assignedUser?->name ?? 'Unassigned')->map->count()->all(),
            'root_cause_distribution' => $incidents->groupBy(fn (OperationalIncident $incident): string => $incident->root_cause_category ?? 'not_set')->map->count()->all(),
            'repeated_sources' => $incidents->whereNotNull('source_id')->groupBy(fn (OperationalIncident $incident): string => $incident->source_type.'#'.$incident->source_id)->filter(fn ($items): bool => $items->count() > 1)->map->count()->all(),
            'rows' => $incidents->map(fn (OperationalIncident $incident): array => [
                'incident_number' => $incident->incident_number,
                'title' => $incident->title,
                'type' => $this->enumValue($incident->incident_type),
                'severity' => $this->enumValue($incident->severity),
                'priority' => $this->enumValue($incident->priority),
                'status' => $this->enumValue($incident->status),
                'sla_status' => $this->enumValue($incident->sla_status),
                'owner' => $incident->assignedUser?->name,
                'source' => trim((string) $incident->source_type.' #'.$incident->source_id),
                'created_at' => $incident->created_at?->toDateTimeString(),
            ])->values()->all(),
            'warnings' => [],
        ];
    }

    private function averageResolutionHours($incidents): ?float
    {
        $durations = $incidents
            ->filter(fn (OperationalIncident $incident): bool => $incident->resolved_at !== null && $incident->created_at !== null)
            ->map(fn (OperationalIncident $incident): int => $incident->created_at->diffInHours($incident->resolved_at));

        return $durations->isEmpty() ? null : round((float) $durations->average(), 2);
    }

    private function enumValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return $value instanceof \BackedEnum ? $value->value : (string) $value;
    }
}
