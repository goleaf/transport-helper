<?php

namespace App\Services\Supply\Incidents;

use App\Enums\IncidentSourceType;
use App\Enums\IncidentType;
use Illuminate\Database\Eloquent\Model;

class IncidentTypeResolver
{
    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function resolveForSource(string $sourceType, Model|string|null $source = null, array $context = []): array
    {
        $incidentType = $this->incidentType($sourceType, $context);
        $label = $context['source_label'] ?? $this->sourceLabel($sourceType, $source, $context);

        return [
            'incident_type' => $incidentType,
            'title' => $context['title'] ?? $this->title($incidentType),
            'description' => $context['description'] ?? $this->description($incidentType),
            'source_label' => $label,
            'source_url' => $context['source_url'] ?? null,
            'metadata' => $context['metadata'] ?? [],
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function incidentType(string $sourceType, array $context): string
    {
        if ($sourceType === IncidentSourceType::ImportBatch->value) {
            return (($context['status'] ?? null) === 'failed')
                ? IncidentType::ImportFailure->value
                : IncidentType::ImportDataQuality->value;
        }

        if ($sourceType === IncidentSourceType::ImportRow->value) {
            return str_contains((string) ($context['error_message'] ?? ''), 'SKU')
                ? IncidentType::UnknownSkuUnresolved->value
                : IncidentType::ImportDataQuality->value;
        }

        if ($sourceType === IncidentSourceType::CalculationRun->value || $sourceType === IncidentSourceType::OrderProposalItem->value) {
            return IncidentType::CalculationWarning->value;
        }

        if ($sourceType === IncidentSourceType::OrderProposal->value) {
            return IncidentType::OrderProposalBlocked->value;
        }

        if ($sourceType === IncidentSourceType::SupplierOrder->value) {
            return IncidentType::SupplierEmailBlocked->value;
        }

        if ($sourceType === IncidentSourceType::EmailMessage->value) {
            return (($context['status'] ?? null) === 'send_failed')
                ? IncidentType::EmailSendFailure->value
                : IncidentType::InboundEmailUnmatched->value;
        }

        if ($sourceType === IncidentSourceType::AiEmailExtraction->value) {
            return IncidentType::AiExtractionNeedsReview->value;
        }

        if ($sourceType === IncidentSourceType::FormAutofillRun->value) {
            return IncidentType::FormAutofillValidationFailure->value;
        }

        if ($sourceType === IncidentSourceType::SupplierConfirmation->value) {
            return IncidentType::SupplierConfirmationMismatch->value;
        }

        if ($sourceType === IncidentSourceType::CarrierQuote->value) {
            return IncidentType::CarrierQuoteNeedsReview->value;
        }

        if ($sourceType === IncidentSourceType::LogisticsRecord->value) {
            return (bool) ($context['receiving_mismatch'] ?? false)
                ? IncidentType::ReceivingMismatch->value
                : IncidentType::LogisticsDelay->value;
        }

        if ($sourceType === IncidentSourceType::HealthCheck->value) {
            return IncidentType::HealthCheckWarning->value;
        }

        return IncidentType::Other->value;
    }

    private function title(string $incidentType): string
    {
        return match ($incidentType) {
            IncidentType::ImportFailure->value => 'Import failed',
            IncidentType::ImportDataQuality->value => 'Import data quality issue',
            IncidentType::CalculationWarning->value => 'Calculation warning requires review',
            IncidentType::OrderProposalBlocked->value => 'Order proposal blocked',
            IncidentType::SupplierEmailBlocked->value => 'Supplier email blocked',
            IncidentType::EmailSendFailure->value => 'Supplier email send failed',
            IncidentType::InboundEmailUnmatched->value => 'Inbound email unmatched',
            IncidentType::AiExtractionNeedsReview->value => 'AI extraction needs human review',
            IncidentType::FormAutofillValidationFailure->value => 'Form autofill validation failed',
            IncidentType::SupplierConfirmationMismatch->value => 'Supplier confirmation mismatch',
            IncidentType::CarrierQuoteNeedsReview->value => 'Carrier quote needs review',
            IncidentType::CarrierSelectionBlocked->value => 'Carrier selection blocked',
            IncidentType::LogisticsDelay->value => 'Logistics delayed',
            IncidentType::ReceivingMismatch->value => 'Receiving mismatch',
            IncidentType::ProcurementGateBlocked->value => 'Procurement gate blocked',
            IncidentType::BudgetOverrun->value => 'Budget overrun',
            IncidentType::UnknownSkuUnresolved->value => 'Unknown SKU unresolved',
            IncidentType::MasterDataDuplicate->value => 'Master data duplicate',
            IncidentType::IntegrationFailure->value => 'Integration failure',
            IncidentType::HealthCheckWarning->value => 'Health check warning',
            IncidentType::SecurityWarning->value => 'Security warning',
            default => 'Operational incident',
        };
    }

    private function description(string $incidentType): string
    {
        return match ($incidentType) {
            IncidentType::LogisticsDelay->value => 'Logistics record is delayed or missing required date progress.',
            IncidentType::ReceivingMismatch->value => 'Receiving data does not match expected quantities or items.',
            IncidentType::AiExtractionNeedsReview->value => 'AI extraction is waiting for human review and does not apply business changes.',
            IncidentType::FormAutofillValidationFailure->value => 'Form autofill run needs correction before it can be validated.',
            default => 'Workflow is blocked and requires an owner to review, resolve and document the outcome.',
        };
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function sourceLabel(string $sourceType, Model|string|null $source, array $context): ?string
    {
        if (isset($context['source_id'])) {
            return str($sourceType)->replace('_', ' ')->title().' #'.$context['source_id'];
        }

        if ($source instanceof Model && $source->getKey() !== null) {
            return str($sourceType)->replace('_', ' ')->title().' #'.$source->getKey();
        }

        return is_string($source) ? $source : null;
    }
}
