<?php

namespace App\Enums;

enum IncidentSeverity: string
{
    case Critical = 'critical';
    case High = 'high';
    case Medium = 'medium';
    case Low = 'low';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $severity): string => $severity->value, self::cases());
    }
}
