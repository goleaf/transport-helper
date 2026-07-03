<?php

namespace App\Enums;

enum ManufacturerFormFormat: string
{
    case Excel = 'excel';
    case Csv = 'csv';
    case Pdf = 'pdf';
    case Json = 'json';
    case PortalManual = 'portal_manual';
}
