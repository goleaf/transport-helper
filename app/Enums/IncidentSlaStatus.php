<?php

namespace App\Enums;

enum IncidentSlaStatus: string
{
    case WithinSla = 'within_sla';
    case ResponseBreached = 'response_breached';
    case ResolutionBreached = 'resolution_breached';
    case CompletedWithinSla = 'completed_within_sla';
    case CompletedBreached = 'completed_breached';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $status): string => $status->value, self::cases());
    }
}
