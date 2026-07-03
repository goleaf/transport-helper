<?php

namespace App\Enums;

enum IntegrationConnectionType: string
{
    case Erp = 'erp';
    case Ecommerce = 'ecommerce';
    case Accounting = 'accounting';
    case Warehouse = 'warehouse';
    case GoogleSheets = 'google_sheets';
    case Email = 'email';
    case Api = 'api';
    case Manual = 'manual';
}
