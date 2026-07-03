<?php

namespace App\Enums;

enum IntegrationProvider: string
{
    case Gmail = 'gmail';
    case MicrosoftGraph = 'microsoft_graph';
    case Imap = 'imap';
    case Smtp = 'smtp';
    case GoogleSheets = 'google_sheets';
    case ExternalAi = 'external_ai';
    case LocalLlm = 'local_llm';
    case ErpApi = 'erp_api';
    case EcommerceApi = 'ecommerce_api';
    case WarehouseApi = 'warehouse_api';
    case Manual = 'manual';
}
