<?php

namespace App\Services\Supply\Logistics;

use App\Models\User;
use App\Notifications\SupplyDatabaseNotification;
use App\Services\Audit\AuditLogService;
use Illuminate\Support\Facades\Schema;

class LogisticsNotificationService
{
    public function __construct(
        private readonly NotificationRecipientResolver $recipientResolver,
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function notify(string $type, array $data = [], array $context = []): array
    {
        if (! Schema::hasTable('notifications')) {
            return [
                'created_count' => 0,
                'skipped_count' => 0,
                'skipped_reason' => 'notifications_table_missing',
            ];
        }

        $recipients = $this->recipientResolver->resolve($type, $context);
        $created = 0;
        $skipped = 0;

        foreach ($recipients as $user) {
            if ($this->isDuplicate($user, $data['unique_key'] ?? null)) {
                $skipped++;

                continue;
            }

            $user->notify(new SupplyDatabaseNotification($type, $data));
            $created++;
        }

        if ($created > 0) {
            $this->auditLogService->write('notification_created', null, $context['user'] ?? null, null, null, [
                'type' => $type,
                'recipient_count' => $created,
                'unique_key' => $data['unique_key'] ?? null,
            ], $data['company_id'] ?? null);
        }

        return [
            'created_count' => $created,
            'skipped_count' => $skipped,
            'skipped_reason' => null,
        ];
    }

    private function isDuplicate(User $user, mixed $uniqueKey): bool
    {
        if (! is_string($uniqueKey) || $uniqueKey === '') {
            return false;
        }

        return $user->notifications()
            ->latest('created_at')
            ->limit(100)
            ->get()
            ->contains(fn ($notification): bool => ($notification->data['unique_key'] ?? null) === $uniqueKey);
    }
}
