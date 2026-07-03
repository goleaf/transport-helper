<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case SupplyManager = 'supply_manager';
    case LogisticsManager = 'logistics_manager';
    case Accountant = 'accountant';
    case Viewer = 'viewer';

    public function canManageSupply(): bool
    {
        return in_array($this, [self::Admin, self::SupplyManager], true);
    }

    public function canManageLogistics(): bool
    {
        return in_array($this, [self::Admin, self::SupplyManager, self::LogisticsManager], true);
    }
}
