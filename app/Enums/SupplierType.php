<?php

namespace App\Enums;

enum SupplierType: string
{
    case Manufacturer = 'manufacturer';
    case Distributor = 'distributor';
    case Carrier = 'carrier';
    case Mixed = 'mixed';
}
