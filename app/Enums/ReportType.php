<?php

namespace App\Enums;

enum ReportType: string
{
    case ManagementDashboard = 'management_dashboard';
    case SupplierPerformance = 'supplier_performance';
    case ForecastAccuracy = 'forecast_accuracy';
    case StockoutRisk = 'stockout_risk';
    case OrderProposalQuality = 'order_proposal_quality';
    case SupplierConfirmationMismatches = 'supplier_confirmation_mismatches';
    case TransportPerformance = 'transport_performance';
    case LogisticsPerformance = 'logistics_performance';
    case ReceivingAccuracy = 'receiving_accuracy';
    case DataQuality = 'data_quality';
    case AuditKpis = 'audit_kpis';
    case OperatorEfficiency = 'operator_efficiency';
    case ImportQuality = 'import_quality';
    case EmailAiReviewQuality = 'email_ai_review_quality';
    case FormAutofillQuality = 'form_autofill_quality';
    case PilotReadiness = 'pilot_readiness';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $type): string => $type->value, self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::ManagementDashboard => 'Management Dashboard',
            self::SupplierPerformance => 'Supplier Performance',
            self::ForecastAccuracy => 'Forecast Accuracy',
            self::StockoutRisk => 'Stockout Risk',
            self::OrderProposalQuality => 'Order Proposal Quality',
            self::SupplierConfirmationMismatches => 'Supplier Confirmation Mismatches',
            self::TransportPerformance => 'Transport Performance',
            self::LogisticsPerformance => 'Logistics Performance',
            self::ReceivingAccuracy => 'Receiving Accuracy',
            self::DataQuality => 'Data Quality',
            self::AuditKpis => 'Audit KPIs',
            self::OperatorEfficiency => 'Operator Efficiency',
            self::ImportQuality => 'Import Quality',
            self::EmailAiReviewQuality => 'Email AI Review Quality',
            self::FormAutofillQuality => 'Form Autofill Quality',
            self::PilotReadiness => 'Pilot Readiness',
        };
    }
}
