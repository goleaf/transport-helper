<?php

namespace App\Enums;

enum SupplierLifecycleStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Blocked = 'blocked';
    case Inactive = 'inactive';
    case Merged = 'merged';
    case Archived = 'archived';
}
