<?php

namespace App\Enums;

enum PilotRunType: string
{
    case ReadinessCheck = 'readiness_check';
    case DataQualityCheck = 'data_quality_check';
    case ImportDryRun = 'import_dry_run';
    case CalculationDryRun = 'calculation_dry_run';
    case EmailDryRun = 'email_dry_run';
    case FormAutofillDryRun = 'form_autofill_dry_run';
    case ConfirmationDryRun = 'confirmation_dry_run';
    case TransportDryRun = 'transport_dry_run';
    case LogisticsDryRun = 'logistics_dry_run';
    case FullUatDryRun = 'full_uat_dry_run';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $type): string => $type->value, self::cases());
    }

    public static function fromDryRunName(string $runType): self
    {
        return self::tryFrom($runType) ?? self::FullUatDryRun;
    }
}
