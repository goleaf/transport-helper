<?php

namespace App\Enums;

enum SupplierProductPriceStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Archived = 'archived';
}
