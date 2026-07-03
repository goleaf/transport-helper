<?php

namespace App\Actions;

use App\Models\SupplyAuditEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class RecordSupplyAuditAction
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function handle(?User $actor, string $event, Model $auditable, array $metadata = []): SupplyAuditEvent
    {
        $auditEvent = new SupplyAuditEvent([
            'actor_id' => $actor?->getKey(),
            'event' => $event,
            'metadata' => $metadata,
            'occurred_at' => now(),
        ]);

        $auditEvent->auditable()->associate($auditable);
        $auditEvent->save();

        return $auditEvent;
    }
}
