<?php

namespace App\Enums;

enum SalesExclusionRuleType: string
{
    case Promotion = 'promotion';
    case Anomaly = 'anomaly';
    case Outlier = 'outlier';
    case OneTimeProject = 'one_time_project';
    case StockCorrection = 'stock_correction';
    case ManualExclusion = 'manual_exclusion';
    case Other = 'other';
}
