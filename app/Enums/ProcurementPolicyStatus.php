<?php

namespace App\Enums;

enum ProcurementPolicyStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Archived = 'archived';
}
