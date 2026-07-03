<?php

namespace App\Services\Supply\Incidents;

use App\Enums\IncidentSourceType;
use App\Enums\IncidentType;
use App\Models\AiEmailExtraction;
use App\Models\CalculationRun;
use App\Models\CarrierQuote;
use App\Models\EmailMessage;
use App\Models\FormAutofillRun;
use App\Models\ImportBatch;
use App\Models\LogisticsRecord;
use App\Models\OrderProposal;
use App\Models\OrderProposalItem;
use App\Models\SupplierConfirmation;
use App\Models\SupplierOrder;
use App\Services\Audit\AuditLogService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class IncidentAutoDetectionService
{
    public function __construct(
        private readonly IncidentCreationService $creationService,
        private readonly IncidentWorkflowLinkService $linkService,
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function detect(array $options = []): array
    {
        $dryRun = (bool) ($options['dry_run'] ?? false);
        $create = (bool) ($options['create_incidents'] ?? ! $dryRun);
        $max = (int) ($options['max_per_type'] ?? 50);
        $types = array_filter((array) ($options['types'] ?? []));
        $findings = [];
        $warnings = [];
        $created = 0;
        $deduped = 0;

        foreach ($this->detectors($options, $max, $types, $warnings) as $finding) {
            $findings[] = $finding;
            if (! $create) {
                continue;
            }

            $result = $this->creationService->createForSource(
                $finding['incident_type'],
                null,
                $finding,
            );
            $result['deduped'] ? $deduped++ : $created++;
        }

        $summary = [
            'checked_types' => array_values(array_unique(array_column($findings, 'incident_type'))),
            'findings_count' => count($findings),
            'incidents_created' => $created,
            'deduped_count' => $deduped,
            'dry_run' => $dryRun,
            'warnings' => $warnings,
            'findings' => $findings,
        ];

        if (! $dryRun) {
            $this->auditLogService->write('incident_detection_completed', null, null, null, null, $summary);
        }

        return $summary;
    }

    /**
     * @param  array<string, mixed>  $options
     * @param  list<string>  $types
     * @param  list<string>  $warnings
     * @return list<array<string, mixed>>
     */
    private function detectors(array $options, int $max, array $types, array &$warnings): array
    {
        $findings = [];
        $findings = array_merge($findings, $this->detectFailedImports($options, $max, $types));
        $findings = array_merge($findings, $this->detectCalculationWarnings($options, $max, $types));
        $findings = array_merge($findings, $this->detectBlockedProposals($options, $max, $types));
        $findings = array_merge($findings, $this->detectEmailFailures($options, $max, $types));
        $findings = array_merge($findings, $this->detectAiReviewBacklog($options, $max, $types));
        $findings = array_merge($findings, $this->detectFormAutofillFailures($options, $max, $types));
        $findings = array_merge($findings, $this->detectConfirmationMismatches($options, $max, $types));
        $findings = array_merge($findings, $this->detectCarrierQuoteIssues($options, $max, $types));
        $findings = array_merge($findings, $this->detectLogisticsDelays($options, $max, $types));
        $findings = array_merge($findings, $this->detectReceivingMismatches($options, $max, $types));

        if (! Schema::hasTable('procurement_approval_requests')) {
            $warnings[] = 'procurement_approval_requests table missing; procurement gate detection skipped.';
        }

        if (! Schema::hasTable('unknown_sku_resolutions')) {
            $warnings[] = 'unknown_sku_resolutions table missing; unknown SKU resolution detection skipped.';
        }

        return $findings;
    }

    /**
     * @param  list<string>  $types
     * @return list<array<string, mixed>>
     */
    public function detectFailedImports(array $options = [], int $max = 50, array $types = []): array
    {
        if (! $this->enabled(IncidentType::ImportFailure->value, $types)) {
            return [];
        }

        return ImportBatch::query()
            ->select(['id', 'company_id', 'source_name', 'status', 'failed_rows', 'total_rows', 'error_summary', 'created_at'])
            ->whereIn('status', ['failed', 'completed_with_errors'])
            ->when(isset($options['company_id']), fn ($query) => $query->where('company_id', $options['company_id']))
            ->orderByDesc('id')
            ->limit($max)
            ->get()
            ->map(fn (ImportBatch $batch): array => $this->finding(
                IncidentType::ImportFailure->value,
                IncidentSourceType::ImportBatch->value,
                $batch,
                'Import failed: '.($batch->source_name ?? 'batch '.$batch->id),
                $batch->error_summary,
            ))
            ->all();
    }

    /**
     * @param  list<string>  $types
     * @return list<array<string, mixed>>
     */
    public function detectCalculationWarnings(array $options = [], int $max = 50, array $types = []): array
    {
        if (! $this->enabled(IncidentType::CalculationWarning->value, $types)) {
            return [];
        }

        $findings = CalculationRun::query()
            ->select(['id', 'company_id', 'supplier_id', 'status', 'created_at'])
            ->where('status', 'failed')
            ->when(isset($options['company_id']), fn ($query) => $query->where('company_id', $options['company_id']))
            ->orderByDesc('id')
            ->limit($max)
            ->get()
            ->map(fn (CalculationRun $run): array => $this->finding(
                IncidentType::CalculationWarning->value,
                IncidentSourceType::CalculationRun->value,
                $run,
                'Calculation run failed',
                'Calculation run failed and needs operator review.',
            ))
            ->all();

        return array_merge($findings, OrderProposalItem::query()
            ->select(['id', 'order_proposal_id', 'product_id', 'status', 'requires_human_review', 'warnings_json', 'created_at'])
            ->where('requires_human_review', true)
            ->orderByDesc('id')
            ->limit($max)
            ->get()
            ->map(fn (OrderProposalItem $item): array => $this->finding(
                IncidentType::CalculationWarning->value,
                IncidentSourceType::OrderProposalItem->value,
                $item,
                'Calculation item needs review',
                'Proposal item requires human review before approval.',
            ))
            ->all());
    }

    /**
     * @param  list<string>  $types
     * @return list<array<string, mixed>>
     */
    public function detectBlockedProposals(array $options = [], int $max = 50, array $types = []): array
    {
        if (! $this->enabled(IncidentType::OrderProposalBlocked->value, $types)) {
            return [];
        }

        $threshold = now()->subHours((int) config('supply.incidents.detection_thresholds.proposal_review_overdue_hours', 48));

        return OrderProposal::query()
            ->select(['id', 'company_id', 'supplier_id', 'status', 'created_at'])
            ->where('status', 'needs_review')
            ->where('created_at', '<=', $threshold)
            ->when(isset($options['company_id']), fn ($query) => $query->where('company_id', $options['company_id']))
            ->orderBy('id')
            ->limit($max)
            ->get()
            ->map(fn (OrderProposal $proposal): array => $this->finding(
                IncidentType::OrderProposalBlocked->value,
                IncidentSourceType::OrderProposal->value,
                $proposal,
                'Order proposal review overdue',
                'Proposal is still waiting for review.',
            ))
            ->all();
    }

    /**
     * @param  list<string>  $types
     * @return list<array<string, mixed>>
     */
    public function detectEmailFailures(array $options = [], int $max = 50, array $types = []): array
    {
        $findings = [];
        if ($this->enabled(IncidentType::EmailSendFailure->value, $types)) {
            $findings = array_merge($findings, EmailMessage::query()
                ->select(['id', 'company_id', 'subject', 'status', 'created_at'])
                ->where('status', 'send_failed')
                ->when(isset($options['company_id']), fn ($query) => $query->where('company_id', $options['company_id']))
                ->orderByDesc('id')
                ->limit($max)
                ->get()
                ->map(fn (EmailMessage $email): array => $this->finding(
                    IncidentType::EmailSendFailure->value,
                    IncidentSourceType::EmailMessage->value,
                    $email,
                    'Supplier email send failed',
                    'Outbound email failed and needs operator review.',
                ))
                ->all());
        }

        if ($this->enabled(IncidentType::SupplierEmailBlocked->value, $types)) {
            $threshold = now()->subHours((int) config('supply.incidents.detection_thresholds.email_approval_overdue_hours', 24));
            $findings = array_merge($findings, SupplierOrder::query()
                ->select(['id', 'company_id', 'order_number', 'status', 'created_at', 'email_approved_at'])
                ->where('status', 'email_prepared')
                ->whereNull('email_approved_at')
                ->where('created_at', '<=', $threshold)
                ->when(isset($options['company_id']), fn ($query) => $query->where('company_id', $options['company_id']))
                ->orderBy('id')
                ->limit($max)
                ->get()
                ->map(fn (SupplierOrder $order): array => $this->finding(
                    IncidentType::SupplierEmailBlocked->value,
                    IncidentSourceType::SupplierOrder->value,
                    $order,
                    'Supplier email approval overdue',
                    'Supplier order email is prepared but not approved.',
                ))
                ->all());
        }

        return $findings;
    }

    /**
     * @param  list<string>  $types
     * @return list<array<string, mixed>>
     */
    public function detectAiReviewBacklog(array $options = [], int $max = 50, array $types = []): array
    {
        if (! $this->enabled(IncidentType::AiExtractionNeedsReview->value, $types)) {
            return [];
        }

        $threshold = now()->subHours((int) config('supply.incidents.detection_thresholds.ai_review_overdue_hours', 24));

        return AiEmailExtraction::query()
            ->select(['id', 'email_message_id', 'confidence', 'requires_human_review', 'review_reason', 'created_at'])
            ->where('requires_human_review', true)
            ->whereNull('reviewed_at')
            ->where('created_at', '<=', $threshold)
            ->orderBy('id')
            ->limit($max)
            ->get()
            ->map(fn (AiEmailExtraction $extraction): array => $this->finding(
                IncidentType::AiExtractionNeedsReview->value,
                IncidentSourceType::AiEmailExtraction->value,
                $extraction,
                'AI extraction review overdue',
                'AI extraction is still waiting for human review.',
            ))
            ->all();
    }

    /**
     * @param  list<string>  $types
     * @return list<array<string, mixed>>
     */
    public function detectFormAutofillFailures(array $options = [], int $max = 50, array $types = []): array
    {
        if (! $this->enabled(IncidentType::FormAutofillValidationFailure->value, $types)) {
            return [];
        }

        $threshold = now()->subHours((int) config('supply.incidents.detection_thresholds.form_review_overdue_hours', 24));

        return FormAutofillRun::query()
            ->select(['id', 'company_id', 'status', 'validation_errors_json', 'created_at'])
            ->whereIn('status', ['failed', 'needs_review'])
            ->where('created_at', '<=', $threshold)
            ->when(isset($options['company_id']), fn ($query) => $query->where('company_id', $options['company_id']))
            ->orderBy('id')
            ->limit($max)
            ->get()
            ->map(fn (FormAutofillRun $run): array => $this->finding(
                IncidentType::FormAutofillValidationFailure->value,
                IncidentSourceType::FormAutofillRun->value,
                $run,
                'Form autofill needs review',
                'Form autofill run is failed or waiting for review.',
            ))
            ->all();
    }

    /**
     * @param  list<string>  $types
     * @return list<array<string, mixed>>
     */
    public function detectConfirmationMismatches(array $options = [], int $max = 50, array $types = []): array
    {
        if (! $this->enabled(IncidentType::SupplierConfirmationMismatch->value, $types)) {
            return [];
        }

        return SupplierConfirmation::query()
            ->select(['id', 'company_id', 'supplier_order_id', 'status', 'discrepancy_summary', 'created_at'])
            ->whereIn('status', ['needs_review', 'quantity_mismatch', 'date_mismatch'])
            ->when(isset($options['company_id']), fn ($query) => $query->where('company_id', $options['company_id']))
            ->orderByDesc('id')
            ->limit($max)
            ->get()
            ->map(fn (SupplierConfirmation $confirmation): array => $this->finding(
                IncidentType::SupplierConfirmationMismatch->value,
                IncidentSourceType::SupplierConfirmation->value,
                $confirmation,
                'Supplier confirmation mismatch',
                $confirmation->discrepancy_summary ?? 'Supplier confirmation requires mismatch review.',
            ))
            ->all();
    }

    /**
     * @param  list<string>  $types
     * @return list<array<string, mixed>>
     */
    public function detectCarrierQuoteIssues(array $options = [], int $max = 50, array $types = []): array
    {
        if (! $this->enabled(IncidentType::CarrierQuoteNeedsReview->value, $types)) {
            return [];
        }

        return CarrierQuote::query()
            ->select(['id', 'company_id', 'supplier_order_id', 'carrier_id', 'status', 'validation_errors_json', 'created_at'])
            ->where('status', 'needs_review')
            ->when(isset($options['company_id']), fn ($query) => $query->where('company_id', $options['company_id']))
            ->orderByDesc('id')
            ->limit($max)
            ->get()
            ->map(fn (CarrierQuote $quote): array => $this->finding(
                IncidentType::CarrierQuoteNeedsReview->value,
                IncidentSourceType::CarrierQuote->value,
                $quote,
                'Carrier quote needs review',
                'Carrier quote is blocked until a user reviews it.',
            ))
            ->all();
    }

    /**
     * @param  list<string>  $types
     * @return list<array<string, mixed>>
     */
    public function detectLogisticsDelays(array $options = [], int $max = 50, array $types = []): array
    {
        if (! $this->enabled(IncidentType::LogisticsDelay->value, $types)) {
            return [];
        }

        return LogisticsRecord::query()
            ->select(['id', 'company_id', 'supplier_order_id', 'supplier_id', 'status', 'delivery_date', 'actual_received_date', 'delay_reason', 'created_at'])
            ->where(function ($query): void {
                $query->whereIn('status', ['delayed', 'needs_review'])
                    ->orWhere(function ($query): void {
                        $query->whereNotNull('delivery_date')
                            ->whereNull('actual_received_date')
                            ->where('delivery_date', '<', now()->toDateString());
                    });
            })
            ->when(isset($options['company_id']), fn ($query) => $query->where('company_id', $options['company_id']))
            ->orderBy('id')
            ->limit($max)
            ->get()
            ->map(fn (LogisticsRecord $record): array => $this->finding(
                IncidentType::LogisticsDelay->value,
                IncidentSourceType::LogisticsRecord->value,
                $record,
                'Logistics delay',
                $record->delay_reason ?? 'Logistics record is delayed or needs review.',
            ))
            ->all();
    }

    /**
     * @param  list<string>  $types
     * @return list<array<string, mixed>>
     */
    public function detectReceivingMismatches(array $options = [], int $max = 50, array $types = []): array
    {
        if (! $this->enabled(IncidentType::ReceivingMismatch->value, $types)) {
            return [];
        }

        return LogisticsRecord::query()
            ->select(['id', 'company_id', 'supplier_order_id', 'status', 'receiving_discrepancies_json', 'created_at'])
            ->whereNotNull('receiving_discrepancies_json')
            ->when(isset($options['company_id']), fn ($query) => $query->where('company_id', $options['company_id']))
            ->orderByDesc('id')
            ->limit($max)
            ->get()
            ->map(fn (LogisticsRecord $record): array => $this->finding(
                IncidentType::ReceivingMismatch->value,
                IncidentSourceType::LogisticsRecord->value,
                $record,
                'Receiving mismatch',
                'Receiving discrepancies are present and require review.',
                ['receiving_mismatch' => true],
            ))
            ->all();
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function finding(string $type, string $sourceType, Model $source, string $title, ?string $description, array $extra = []): array
    {
        return [
            'incident_type' => $type,
            'company_id' => $source->getAttribute('company_id'),
            'source_type' => $sourceType,
            'source_id' => $source->getKey(),
            'source_label' => $this->linkService->sourceLabel($sourceType, (int) $source->getKey()),
            'source_url' => $this->linkService->sourceUrl($sourceType, (int) $source->getKey()),
            'title' => $title,
            'description' => $description,
            'metadata' => $extra,
        ];
    }

    /**
     * @param  list<string>  $types
     */
    private function enabled(string $incidentType, array $types): bool
    {
        return $types === [] || in_array($incidentType, $types, true);
    }
}
