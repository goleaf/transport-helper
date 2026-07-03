<?php

namespace App\Enums;

enum PilotRunStatus: string
{
    case Draft = 'draft';
    case Running = 'running';
    case Passed = 'passed';
    case PassedWithWarnings = 'passed_with_warnings';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $status): string => $status->value, self::cases());
    }
}
