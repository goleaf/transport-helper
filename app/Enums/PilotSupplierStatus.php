<?php

namespace App\Enums;

enum PilotSupplierStatus: string
{
    case Draft = 'draft';
    case Configuring = 'configuring';
    case ReadyForDryRun = 'ready_for_dry_run';
    case DryRunPassed = 'dry_run_passed';
    case ReadyForUat = 'ready_for_uat';
    case UatPassed = 'uat_passed';
    case ApprovedForLive = 'approved_for_live';
    case Blocked = 'blocked';
    case Archived = 'archived';

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
            self::Draft->value,
            self::Configuring->value,
            self::ReadyForDryRun->value,
            self::DryRunPassed->value,
            self::ReadyForUat->value,
            self::UatPassed->value,
            self::ApprovedForLive->value,
            self::Blocked->value,
        ];
    }
}
