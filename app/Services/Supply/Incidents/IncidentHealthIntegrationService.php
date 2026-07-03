<?php

namespace App\Services\Supply\Incidents;

use App\Enums\IncidentSeverity;
use App\Enums\IncidentSlaStatus;
use App\Enums\IncidentStatus;
use App\Models\IncidentCorrectiveAction;
use App\Models\OperationalIncident;

class IncidentHealthIntegrationService
{
    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function healthSummary(array $options = []): array
    {
        $base = OperationalIncident::query()
            ->select(['id', 'company_id', 'severity', 'status', 'sla_status', 'assigned_user_id', 'created_at'])
            ->when(isset($options['company_id']), fn ($query) => $query->where('company_id', $options['company_id']));

        $openCritical = (clone $base)
            ->where('severity', IncidentSeverity::Critical->value)
            ->whereIn('status', IncidentStatus::activeValues())
            ->count();
        $slaBreached = (clone $base)
            ->whereIn('sla_status', [IncidentSlaStatus::ResponseBreached->value, IncidentSlaStatus::ResolutionBreached->value, IncidentSlaStatus::CompletedBreached->value])
            ->count();
        $unassigned = (clone $base)
            ->whereIn('status', IncidentStatus::activeValues())
            ->whereNull('assigned_user_id')
            ->count();
        $overdueActions = IncidentCorrectiveAction::query()
            ->whereNotIn('status', ['done', 'verified', 'cancelled'])
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->toDateString())
            ->count();

        $status = $openCritical > 0 || $slaBreached > 0 ? 'error' : ($unassigned > 0 || $overdueActions > 0 ? 'warning' : 'ok');

        return [
            'status' => $status,
            'open_critical_count' => $openCritical,
            'sla_breached_count' => $slaBreached,
            'unassigned_count' => $unassigned,
            'overdue_corrective_actions' => $overdueActions,
        ];
    }
}
