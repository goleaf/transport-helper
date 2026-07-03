<?php

namespace App\Enums;

enum FormTemplateContextType: string
{
    case SupplierOrder = 'supplier_order';
    case SupplierConfirmation = 'supplier_confirmation';
    case ReadyDateUpdate = 'ready_date_update';
    case QuantityMismatch = 'quantity_mismatch';
    case CarrierQuote = 'carrier_quote';
    case LogisticsUpdate = 'logistics_update';
    case CustomEmailForm = 'custom_email_form';
}
