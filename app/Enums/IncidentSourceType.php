<?php

namespace App\Enums;

enum IncidentSourceType: string
{
    case ImportBatch = 'import_batch';
    case ImportRow = 'import_row';
    case CalculationRun = 'calculation_run';
    case OrderProposal = 'order_proposal';
    case OrderProposalItem = 'order_proposal_item';
    case SupplierOrder = 'supplier_order';
    case EmailMessage = 'email_message';
    case AiEmailExtraction = 'ai_email_extraction';
    case FormAutofillRun = 'form_autofill_run';
    case SupplierConfirmation = 'supplier_confirmation';
    case CarrierQuote = 'carrier_quote';
    case LogisticsRecord = 'logistics_record';
    case ProcurementApprovalRequest = 'procurement_approval_request';
    case ProcurementException = 'procurement_exception';
    case UnknownSkuResolution = 'unknown_sku_resolution';
    case HealthCheck = 'health_check';
    case Manual = 'manual';
    case Other = 'other';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $type): string => $type->value, self::cases());
    }
}
