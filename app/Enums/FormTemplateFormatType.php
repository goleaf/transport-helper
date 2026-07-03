<?php

namespace App\Enums;

enum FormTemplateFormatType: string
{
    case InternalHtml = 'internal_html';
    case Excel = 'excel';
    case Csv = 'csv';
    case Pdf = 'pdf';
    case Json = 'json';
    case PortalManual = 'portal_manual';
    case PortalAutomationPlaceholder = 'portal_automation_placeholder';
}
