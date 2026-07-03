<?php

namespace App\Services\Supply\Analytics;

use App\Models\AiEmailExtraction;
use App\Models\User;

class EmailAiReviewQualityReportService
{
    public function __construct(private readonly AnalyticsFilterService $filters) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function report(array $filters = [], ?User $user = null): array
    {
        $normalized = $this->filters->normalize($filters);
        $extractions = AiEmailExtraction::query()
            ->select(['id', 'email_message_id', 'confidence', 'requires_human_review', 'reviewed_at', 'accepted_at', 'rejected_at', 'output_json', 'created_at'])
            ->with(['emailMessage:id,direction,status'])
            ->latest('id')
            ->limit(500)
            ->get();

        $accepted = $extractions->whereNotNull('accepted_at')->count();
        $rejected = $extractions->whereNotNull('rejected_at')->count();
        $lowConfidence = $extractions->filter(fn (AiEmailExtraction $extraction): bool => (float) $extraction->confidence < 80)->count();

        return [
            'type' => 'email_ai_review_quality',
            'title' => 'Email AI Review Quality',
            'description' => 'Human review outcomes for AI email extraction suggestions.',
            'filters' => $normalized,
            'summary' => [
                'total_ai_extractions' => $extractions->count(),
                'accepted' => $accepted,
                'rejected' => $rejected,
                'needs_review' => $extractions->where('requires_human_review', true)->count(),
                'acceptance_rate' => $this->percentage($accepted, $extractions->count()),
                'rejection_rate' => $this->percentage($rejected, $extractions->count()),
                'low_confidence_count' => $lowConfidence,
                'unknown_sku_count' => $extractions->filter(fn (AiEmailExtraction $extraction): bool => (int) ($extraction->output_json['unknown_sku_count'] ?? 0) > 0)->count(),
                'quantity_mismatch_count' => $extractions->filter(fn (AiEmailExtraction $extraction): bool => (bool) ($extraction->output_json['quantity_mismatch'] ?? false))->count(),
                'average_time_to_review_hours' => $this->averageHours($extractions),
            ],
            'rows' => $extractions->map(fn (AiEmailExtraction $extraction): array => [
                'extraction_id' => $extraction->id,
                'confidence' => (float) $extraction->confidence,
                'requires_human_review' => (bool) $extraction->requires_human_review,
                'accepted' => $extraction->accepted_at !== null,
                'rejected' => $extraction->rejected_at !== null,
            ])->values()->all(),
            'messages' => ['AI suggestions are reviewed by humans and are not authoritative.'],
            'warnings' => array_merge($normalized['warnings'], $extractions->isEmpty() ? ['No AI extraction review data found.'] : []),
            'definitions' => app(KpiDefinitionService::class)->definitions(),
        ];
    }

    private function percentage(int|float $value, int|float $total): float
    {
        return $total > 0 ? round(($value / $total) * 100, 2) : 0.0;
    }

    private function averageHours($extractions): float
    {
        $hours = $extractions
            ->filter(fn (AiEmailExtraction $extraction): bool => $extraction->reviewed_at !== null)
            ->map(fn (AiEmailExtraction $extraction): float => $extraction->created_at->diffInMinutes($extraction->reviewed_at) / 60);

        return round((float) $hours->avg(), 2);
    }
}
