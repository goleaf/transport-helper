<?php

namespace App\Enums;

enum IncidentPriority: string
{
    case P1 = 'p1';
    case P2 = 'p2';
    case P3 = 'p3';
    case P4 = 'p4';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $priority): string => $priority->value, self::cases());
    }
}
