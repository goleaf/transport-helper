<?php

namespace App\Services\Supply\Incidents;

use App\Enums\IncidentSourceType;
use App\Enums\IncidentStatus;
use App\Enums\IncidentType;
use App\Models\OperationalIncident;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class IncidentCreationService
{
    public function __construct(
        private readonly IncidentTypeResolver $typeResolver,
        private readonly IncidentSeverityResolver $severityResolver,
        private readonly IncidentSlaService $slaService,
        private readonly IncidentAssignmentService $assignmentService,
        private readonly IncidentNotificationService $notificationService,
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public function create(array $validated, ?User $user = null): array
    {
        $incidentType = (string) ($validated['incident_type'] ?? IncidentType::Other->value);
        $severity = $this->severityResolver->resolve($incidentType, $validated);
        $sourceType = $validated['source_type'] ?? IncidentSourceType::Manual->value;
        $sourceId = isset($validated['source_id']) ? (int) $validated['source_id'] : null;
        $companyId = isset($validated['company_id']) ? (int) $validated['company_id'] : null;

        $duplicate = $this->activeDuplicate($companyId, $incidentType, (string) $sourceType, $sourceId);
        if ($duplicate instanceof OperationalIncident) {
            $duplicate->increment('occurrence_count');
            $duplicate->forceFill(['last_seen_at' => now()])->save();
            $duplicate->events()->create([
                'event_type' => 'incident_deduped',
                'metadata_json' => ['title' => $validated['title'] ?? $duplicate->title],
                'created_by_user_id' => $user?->id,
                'created_at' => now(),
            ]);
            $this->auditLogService->write('operational_incident_deduped', $duplicate, $user, null, null, [
                'occurrence_count' => $duplicate->occurrence_count,
            ], $duplicate->company_id);

            return ['incident' => $duplicate->fresh(), 'deduped' => true];
        }

        $incident = OperationalIncident::query()->create([
            'company_id' => $companyId,
            'incident_number' => $this->nextIncidentNumber(),
            'incident_type' => $incidentType,
            'severity' => $severity['severity'],
            'priority' => $severity['priority'],
            'status' => IncidentStatus::Open->value,
            'title' => (string) $validated['title'],
            'description' => $validated['description'] ?? null,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'source_label' => $validated['source_label'] ?? null,
            'source_url' => $validated['source_url'] ?? null,
            'assigned_user_id' => $validated['assigned_user_id'] ?? null,
            'reported_by_user_id' => $user?->id,
            'occurrence_count' => 1,
            'last_seen_at' => now(),
            'metadata_json' => $this->sanitizeMetadata($validated['metadata_json'] ?? $validated['metadata'] ?? []),
        ]);

        $this->slaService->assignDueDates($incident);
        $incident = $incident->fresh();
        $incident->events()->create([
            'event_type' => 'incident_created',
            'new_values_json' => Arr::only($incident->getAttributes(), ['incident_type', 'severity', 'priority', 'status', 'title']),
            'metadata_json' => ['source_type' => $sourceType, 'source_id' => $sourceId],
            'created_by_user_id' => $user?->id,
            'created_at' => now(),
        ]);

        if ($incident->assigned_user_id === null) {
            $this->assignmentService->autoAssign($incident);
        }

        $this->notificationService->notify($incident->fresh(), 'incident_created', ['user' => $user]);
        $this->auditLogService->write('operational_incident_created', $incident, $user, null, null, [
            'incident_number' => $incident->incident_number,
            'incident_type' => $incidentType,
            'severity' => $severity['severity'],
        ], $incident->company_id);

        return ['incident' => $incident->fresh(), 'deduped' => false];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function createForSource(string $incidentType, Model|string|null $source, array $context = [], ?User $user = null): array
    {
        $sourceType = (string) ($context['source_type'] ?? IncidentSourceType::Other->value);
        $resolved = $this->typeResolver->resolveForSource($sourceType, $source, $context);

        return $this->create([
            'company_id' => $context['company_id'] ?? ($source instanceof Model ? $source->getAttribute('company_id') : null),
            'incident_type' => $incidentType !== '' ? $incidentType : $resolved['incident_type'],
            'title' => $context['title'] ?? $resolved['title'],
            'description' => $context['description'] ?? $resolved['description'],
            'source_type' => $sourceType,
            'source_id' => $context['source_id'] ?? ($source instanceof Model ? $source->getKey() : null),
            'source_label' => $context['source_label'] ?? $resolved['source_label'],
            'source_url' => $context['source_url'] ?? $resolved['source_url'],
            'metadata' => $resolved['metadata'],
        ], $user);
    }

    private function activeDuplicate(?int $companyId, string $incidentType, string $sourceType, ?int $sourceId): ?OperationalIncident
    {
        if (! (bool) config('supply.incidents.dedupe_active_incidents', true) || $sourceId === null) {
            return null;
        }

        return OperationalIncident::query()
            ->select(['id', 'company_id', 'incident_type', 'source_type', 'source_id', 'title', 'occurrence_count'])
            ->active()
            ->where('incident_type', $incidentType)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->when($companyId === null, fn ($query) => $query->whereNull('company_id'), fn ($query) => $query->where('company_id', $companyId))
            ->first();
    }

    private function nextIncidentNumber(): string
    {
        $prefix = 'INC-'.now()->format('Ymd').'-';
        $count = OperationalIncident::query()
            ->where('incident_number', 'like', $prefix.'%')
            ->count();

        return $prefix.str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * @return array<string, mixed>
     */
    private function sanitizeMetadata(mixed $metadata): array
    {
        if (! is_array($metadata)) {
            return [];
        }

        return collect($metadata)
            ->reject(fn (mixed $value, string|int $key): bool => str_contains((string) $key, 'secret') || str_contains((string) $key, 'password') || str_contains((string) $key, 'token') || str_contains((string) $key, 'encrypted_config'))
            ->all();
    }
}
