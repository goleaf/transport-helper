<?php

namespace App\Services\Supply\Transport;

use App\Models\SupplierOrder;
use App\Services\Audit\AuditLogService;

class CarrierQuoteComparisonService
{
    public function __construct(
        private readonly CarrierQuoteScoringService $scoringService,
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * @param  array<string, mixed>  $requirements
     * @return array<string, mixed>
     */
    public function compareForOrder(SupplierOrder $order, array $requirements = []): array
    {
        $quotes = $order->carrierQuotes()
            ->select(['id', 'company_id', 'supplier_order_id', 'carrier_id', 'price', 'currency', 'pickup_date', 'delivery_date', 'transit_days', 'conditions', 'reliability_score', 'calculated_score', 'score_explanation_json', 'status'])
            ->with(['carrier:id,name,reliability_score'])
            ->get();

        $ranked = $quotes->map(function ($quote) use ($quotes, $requirements): array {
            $score = $this->scoringService->score($quote, $requirements + ['competing_quotes' => $quotes]);
            $quote->forceFill([
                'calculated_score' => $score['score'],
                'score_explanation_json' => $score['explanation'],
            ])->save();

            return [
                'quote' => $quote->refresh(),
                'score' => $score['score'],
                'warnings' => $score['warnings'],
                'explanation' => $score['explanation'],
            ];
        })->sortByDesc('score')->values();

        $bestQuoteId = $ranked->first()['quote']->id ?? null;

        $this->auditLogService->write('carrier_quotes_compared', $order, null, null, null, [
            'supplier_order_id' => $order->id,
            'quote_count' => $quotes->count(),
            'best_quote_id' => $bestQuoteId,
            'requires_human_selection' => true,
        ], $order->company_id);

        return [
            'supplier_order' => $order,
            'ranked_quotes' => $ranked->all(),
            'best_quote_id' => $bestQuoteId,
            'requires_human_selection' => true,
            'message' => 'User must select carrier. System recommendation is not automatic selection.',
        ];
    }
}
