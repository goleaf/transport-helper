<?php

namespace App\Services\Supply\Analytics;

use App\Models\AuditLog;
use App\Models\User;

class AuditKpiReportService
{
    public function __construct(private readonly AnalyticsFilterService $filters) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function report(array $filters = [], ?User $user = null): array
    {
        $normalized = $this->filters->normalize($filters);
        $createdFrom = $normalized['date_from'].' 00:00:00';
        $createdTo = $normalized['date_to'].' 23:59:59';
        $logs = AuditLog::query()
            ->select(['id', 'company_id', 'user_id', 'event_type', 'metadata_json', 'created_at'])
            ->when($normalized['company_id'], fn ($query, int $companyId) => $query->where('company_id', $companyId))
            ->whereBetween('created_at', [$createdFrom, $createdTo])
            ->with(['user:id,name'])
            ->latest('id')
            ->limit(1000)
            ->get();

        $eventsByType = $logs
            ->groupBy('event_type')
            ->map(fn ($group, string $event): array => ['event_type' => $event, 'count' => $group->count()])
            ->values()
            ->all();

        $actionsByUser = $logs
            ->groupBy(fn (AuditLog $log): string => $log->user?->name ?? 'System')
            ->map(fn ($group, string $name): array => ['user' => $name, 'count' => $group->count()])
            ->values()
            ->all();

        return [
            'type' => 'audit_kpis',
            'title' => 'Audit KPIs',
            'description' => 'Audit event counts, user actions and critical event coverage indicators.',
            'filters' => $normalized,
            'summary' => [
                'total_events' => $logs->count(),
                'events_without_user_id_count' => $logs->whereNull('user_id')->count(),
                'manual_overrides_count' => $logs->filter(fn (AuditLog $log): bool => str_contains($log->event_type, 'override') || str_contains($log->event_type, 'adjusted'))->count(),
                'review_actions_count' => $logs->filter(fn (AuditLog $log): bool => str_contains($log->event_type, 'review') || str_contains($log->event_type, 'approved'))->count(),
            ],
            'events_by_type' => $eventsByType,
            'actions_by_user' => $actionsByUser,
            'critical_event_list' => [
                'order_quantity_adjusted',
                'supplier_email_sent',
                'supplier_confirmation_applied',
                'carrier_selected',
                'goods_receipt_recorded',
            ],
            'rows' => $eventsByType,
            'warnings' => array_merge($normalized['warnings'], $logs->isEmpty() ? ['No audit events found for the selected period.'] : []),
            'definitions' => app(KpiDefinitionService::class)->definitions(),
        ];
    }
}
