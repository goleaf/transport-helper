<?php

namespace App\Enums;

enum IncidentStatus: string
{
    case Open = 'open';
    case Triaged = 'triaged';
    case InProgress = 'in_progress';
    case WaitingOnUser = 'waiting_on_user';
    case WaitingOnSupplier = 'waiting_on_supplier';
    case WaitingOnExternal = 'waiting_on_external';
    case Resolved = 'resolved';
    case Closed = 'closed';
    case Cancelled = 'cancelled';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $status): string => $status->value, self::cases());
    }

    /**
     * @return list<string>
     */
    public static function activeValues(): array
    {
        return [
            self::Open->value,
            self::Triaged->value,
            self::InProgress->value,
            self::WaitingOnUser->value,
            self::WaitingOnSupplier->value,
            self::WaitingOnExternal->value,
        ];
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Closed, self::Cancelled], true);
    }
}
