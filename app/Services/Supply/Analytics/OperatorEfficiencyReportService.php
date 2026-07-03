<?php

namespace App\Services\Supply\Analytics;

use App\Models\AiEmailExtraction;
use App\Models\CarrierQuote;
use App\Models\FormAutofillRun;
use App\Models\LogisticsRecord;
use App\Models\OrderProposal;
use App\Models\SupplierOrder;
use App\Models\User;

class OperatorEfficiencyReportService
{
    public function __construct(private readonly AnalyticsFilterService $filters) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function report(array $filters = [], ?User $user = null): array
    {
        $normalized = $this->filters->normalize($filters);
        $proposals = OrderProposal::query()->select(['id', 'created_at', 'approved_at'])->whereNotNull('approved_at')->limit(500)->get();
        $orders = SupplierOrder::query()->select(['id', 'email_approved_at', 'sent_at'])->whereNotNull('sent_at')->limit(500)->get();
        $extractions = AiEmailExtraction::query()->select(['id', 'created_at', 'reviewed_at'])->whereNotNull('reviewed_at')->limit(500)->get();
        $forms = FormAutofillRun::query()->select(['id', 'created_at', 'applied_at'])->limit(500)->get();
        $quotes = CarrierQuote::query()->select(['id', 'created_at', 'selected_at'])->whereNotNull('selected_at')->limit(500)->get();
        $logistics = LogisticsRecord::query()->select(['id', 'delivery_date', 'actual_received_date'])->whereNotNull('actual_received_date')->limit(500)->get();

        $summary = [
            'average_time_proposal_created_to_approved_hours' => $this->averageHours($proposals, 'created_at', 'approved_at'),
            'average_time_email_prepared_to_sent_hours' => $this->averageHours($orders, 'email_approved_at', 'sent_at'),
            'average_time_inbound_email_to_extraction_review_hours' => $this->averageHours($extractions, 'created_at', 'reviewed_at'),
            'average_time_form_autofill_created_to_validated_hours' => $this->averageHours($forms, 'created_at', 'applied_at'),
            'average_time_quote_received_to_carrier_selected_hours' => $this->averageHours($quotes, 'created_at', 'selected_at'),
            'average_time_delivery_to_receiving_record_hours' => $this->averageHours($logistics, 'delivery_date', 'actual_received_date'),
            'manual_adjustments_count' => 0,
            'overrides_count' => 0,
        ];
        $summary['bottleneck_stages'] = collect($summary)
            ->filter(fn (mixed $value, string $key): bool => str_starts_with($key, 'average_time') && (float) $value > 0)
            ->sortDesc()
            ->keys()
            ->take(3)
            ->values()
            ->all();

        return [
            'type' => 'operator_efficiency',
            'title' => 'Operator Efficiency',
            'description' => 'Cycle time and bottleneck reporting for review workflows.',
            'filters' => $normalized,
            'summary' => $summary,
            'rows' => collect($summary['bottleneck_stages'])->map(fn (string $stage): array => ['stage' => $stage, 'hours' => $summary[$stage]])->all(),
            'warnings' => $normalized['warnings'],
            'definitions' => app(KpiDefinitionService::class)->definitions(),
        ];
    }

    private function averageHours($records, string $from, string $to): float
    {
        $hours = $records
            ->filter(fn ($record): bool => $record->{$from} !== null && $record->{$to} !== null)
            ->map(fn ($record): float => $record->{$from}->diffInMinutes($record->{$to}) / 60);

        return round((float) $hours->avg(), 2);
    }
}
