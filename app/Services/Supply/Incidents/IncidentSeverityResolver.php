<?php

namespace App\Services\Supply\Incidents;

use App\Enums\IncidentPriority;
use App\Enums\IncidentSeverity;
use App\Enums\IncidentType;

class IncidentSeverityResolver
{
    /**
     * @param  array<string, mixed>  $context
     * @return array{severity:string,priority:string,reasons:list<string>}
     */
    public function resolve(string $incidentType, array $context = []): array
    {
        $severity = $context['severity'] ?? $this->defaultSeverity($incidentType, $context);
        $priority = $context['priority'] ?? $this->priorityForSeverity((string) $severity);

        return [
            'severity' => (string) $severity,
            'priority' => (string) $priority,
            'reasons' => $this->reasons($incidentType, (string) $severity),
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function defaultSeverity(string $incidentType, array $context): string
    {
        if ($incidentType === IncidentType::SecurityWarning->value) {
            return IncidentSeverity::Critical->value;
        }

        if ($incidentType === IncidentType::UnknownSkuUnresolved->value && ($context['source_type'] ?? null) === 'supplier_confirmation') {
            return IncidentSeverity::High->value;
        }

        return match ($incidentType) {
            IncidentType::ImportFailure->value,
            IncidentType::SupplierConfirmationMismatch->value,
            IncidentType::CarrierQuoteNeedsReview->value,
            IncidentType::CarrierSelectionBlocked->value,
            IncidentType::LogisticsDelay->value,
            IncidentType::ReceivingMismatch->value,
            IncidentType::ProcurementGateBlocked->value,
            IncidentType::BudgetOverrun->value,
            IncidentType::IntegrationFailure->value => IncidentSeverity::High->value,
            IncidentType::AiExtractionNeedsReview->value,
            IncidentType::FormAutofillValidationFailure->value,
            IncidentType::CalculationWarning->value,
            IncidentType::OrderProposalBlocked->value,
            IncidentType::SupplierEmailBlocked->value,
            IncidentType::EmailSendFailure->value,
            IncidentType::InboundEmailUnmatched->value,
            IncidentType::HealthCheckWarning->value => IncidentSeverity::Medium->value,
            IncidentType::MasterDataDuplicate->value,
            IncidentType::ImportDataQuality->value => IncidentSeverity::Low->value,
            default => IncidentSeverity::Low->value,
        };
    }

    private function priorityForSeverity(string $severity): string
    {
        return match ($severity) {
            IncidentSeverity::Critical->value => IncidentPriority::P1->value,
            IncidentSeverity::High->value => IncidentPriority::P2->value,
            IncidentSeverity::Medium->value => IncidentPriority::P3->value,
            default => IncidentPriority::P4->value,
        };
    }

    /**
     * @return list<string>
     */
    private function reasons(string $incidentType, string $severity): array
    {
        return [
            'incident_type:'.$incidentType,
            'severity:'.$severity,
        ];
    }
}
