<?php

namespace App\Services\Supply\Incidents;

use App\Enums\UserRole;
use App\Models\OperationalIncident;
use App\Models\User;
use App\Notifications\SupplyDatabaseNotification;
use App\Services\Audit\AuditLogService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class IncidentNotificationService
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function notify(OperationalIncident $incident, string $eventType, array $context = []): array
    {
        if (! Schema::hasTable('notifications')) {
            $this->auditLogService->write('incident_notification_skipped', $incident, $context['user'] ?? null, null, null, [
                'reason' => 'notifications_table_missing',
                'event_type' => $eventType,
            ], $incident->company_id);

            return ['created_count' => 0, 'skipped_reason' => 'notifications_table_missing'];
        }

        $recipients = $this->recipients($incident);
        $created = 0;
        foreach ($recipients as $recipient) {
            $uniqueKey = 'incident-'.$eventType.'-'.$incident->id.'-'.$recipient->id;
            if ($this->isDuplicate($recipient, $uniqueKey)) {
                continue;
            }

            $recipient->notify(new SupplyDatabaseNotification('incident_'.$eventType, [
                'title' => 'Incident '.$incident->incident_number,
                'message' => $incident->title,
                'url' => Route::has('supply.incidents.show') ? route('supply.incidents.show', $incident, false) : null,
                'unique_key' => $uniqueKey,
                'operational_incident_id' => $incident->id,
            ]));
            $created++;
        }

        if ($created > 0) {
            $this->auditLogService->write('incident_notification_created', $incident, $context['user'] ?? null, null, null, [
                'event_type' => $eventType,
                'recipient_count' => $created,
            ], $incident->company_id);
        }

        return ['created_count' => $created, 'skipped_reason' => null];
    }

    /**
     * @return list<User>
     */
    private function recipients(OperationalIncident $incident): array
    {
        if ($incident->assigned_user_id !== null) {
            return User::query()->select(['id', 'name', 'email', 'role'])->whereKey($incident->assigned_user_id)->get()->all();
        }

        return User::query()
            ->select(['id', 'name', 'email', 'role'])
            ->whereIn('role', [UserRole::Admin->value, UserRole::SupplyManager->value])
            ->orderBy('id')
            ->limit(5)
            ->get()
            ->all();
    }

    private function isDuplicate(User $user, string $uniqueKey): bool
    {
        return $user->notifications()
            ->latest('created_at')
            ->limit(100)
            ->get()
            ->contains(fn ($notification): bool => ($notification->data['unique_key'] ?? null) === $uniqueKey);
    }
}
