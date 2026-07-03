<?php

namespace App\Enums;

enum IncidentType: string
{
    case ImportFailure = 'import_failure';
    case ImportDataQuality = 'import_data_quality';
    case CalculationWarning = 'calculation_warning';
    case OrderProposalBlocked = 'order_proposal_blocked';
    case SupplierEmailBlocked = 'supplier_email_blocked';
    case EmailSendFailure = 'email_send_failure';
    case InboundEmailUnmatched = 'inbound_email_unmatched';
    case AiExtractionNeedsReview = 'ai_extraction_needs_review';
    case FormAutofillValidationFailure = 'form_autofill_validation_failure';
    case SupplierConfirmationMismatch = 'supplier_confirmation_mismatch';
    case CarrierQuoteNeedsReview = 'carrier_quote_needs_review';
    case CarrierSelectionBlocked = 'carrier_selection_blocked';
    case LogisticsDelay = 'logistics_delay';
    case ReceivingMismatch = 'receiving_mismatch';
    case ProcurementGateBlocked = 'procurement_gate_blocked';
    case BudgetOverrun = 'budget_overrun';
    case UnknownSkuUnresolved = 'unknown_sku_unresolved';
    case MasterDataDuplicate = 'master_data_duplicate';
    case IntegrationFailure = 'integration_failure';
    case HealthCheckWarning = 'health_check_warning';
    case SecurityWarning = 'security_warning';
    case Other = 'other';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $type): string => $type->value, self::cases());
    }

    public function label(): string
    {
        return str($this->value)->replace('_', ' ')->title()->toString();
    }
}
