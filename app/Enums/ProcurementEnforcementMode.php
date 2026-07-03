<?php

namespace App\Enums;

enum ProcurementEnforcementMode: string
{
    case Advisory = 'advisory';
    case Enforced = 'enforced';
}
