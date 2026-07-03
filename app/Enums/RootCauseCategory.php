<?php

namespace App\Enums;

enum RootCauseCategory: string
{
    case DataQuality = 'data_quality';
    case SupplierDelay = 'supplier_delay';
    case SupplierMismatch = 'supplier_mismatch';
    case InternalProcess = 'internal_process';
    case UserError = 'user_error';
    case IntegrationFailure = 'integration_failure';
    case SystemBug = 'system_bug';
    case CapacityPlanning = 'capacity_planning';
    case ProcurementControl = 'procurement_control';
    case MasterData = 'master_data';
    case Unknown = 'unknown';
    case Other = 'other';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $category): string => $category->value, self::cases());
    }
}
