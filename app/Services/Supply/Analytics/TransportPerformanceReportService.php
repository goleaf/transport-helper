<?php

namespace App\Services\Supply\Analytics;

use App\Models\CarrierQuote;
use App\Models\User;

class TransportPerformanceReportService
{
    public function __construct(private readonly AnalyticsFilterService $filters) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function report(array $filters = [], ?User $user = null): array
    {
        $normalized = $this->filters->normalize($filters);
        $createdFrom = $normalized['date_from'].' 00:00:00';
        $createdTo = $normalized['date_to'].' 23:59:59';
        $quotes = CarrierQuote::query()
            ->select(['id', 'company_id', 'supplier_order_id', 'carrier_id', 'price', 'currency', 'pickup_date', 'delivery_date', 'reliability_score', 'calculated_score', 'status', 'selected_at', 'selected_by_user_id', 'warnings_json', 'created_at'])
            ->when($normalized['company_id'], fn ($query, int $companyId) => $query->where('company_id', $companyId))
            ->when($normalized['carrier_id'], fn ($query, int $carrierId) => $query->where('carrier_id', $carrierId))
            ->whereBetween('created_at', [$createdFrom, $createdTo])
            ->with(['carrier:id,name'])
            ->latest('id')
            ->limit(500)
            ->get();

        $selected = $quotes->filter(fn (CarrierQuote $quote): bool => $this->status($quote->status) === 'selected');
        $nonLowestSelected = $selected->filter(function (CarrierQuote $quote) use ($quotes): bool {
            $lowest = $quotes->where('supplier_order_id', $quote->supplier_order_id)->min('price');

            return $lowest !== null && (float) $quote->price > (float) $lowest;
        })->count();

        return [
            'type' => 'transport_performance',
            'title' => 'Transport Performance',
            'description' => 'Carrier quote coverage, selected carrier patterns and non-lowest selection reasons.',
            'filters' => $normalized,
            'summary' => [
                'quotes_count' => $quotes->count(),
                'quotes_per_supplier_order' => round($quotes->groupBy('supplier_order_id')->avg(fn ($group) => $group->count()) ?? 0, 2),
                'average_quote_price' => round((float) $quotes->avg('price'), 2),
                'selected_quotes' => $selected->count(),
                'selected_carrier_average_price' => round((float) $selected->avg('price'), 2),
                'selected_carrier_reliability' => round((float) $selected->avg('reliability_score'), 2),
                'needs_review_quote_count' => $quotes->filter(fn (CarrierQuote $quote): bool => $this->status($quote->status) === 'needs_review')->count(),
                'override_selection_count' => $nonLowestSelected,
                'lowest_price_selected_rate' => $this->percentage($selected->count() - $nonLowestSelected, $selected->count()),
                'non_lowest_selected_due_to_date_or_reliability' => $nonLowestSelected,
            ],
            'rows' => $quotes->map(fn (CarrierQuote $quote): array => [
                'quote_id' => $quote->id,
                'carrier' => $quote->carrier?->name,
                'price' => (float) $quote->price,
                'currency' => $quote->currency,
                'pickup_date' => $quote->pickup_date?->toDateString(),
                'delivery_date' => $quote->delivery_date?->toDateString(),
                'reliability_score' => (float) $quote->reliability_score,
                'calculated_score' => (float) $quote->calculated_score,
                'status' => $this->status($quote->status),
                'warnings' => $quote->warnings_json ?? [],
            ])->values()->all(),
            'messages' => ['Lowest price is not automatically treated as the best carrier choice.'],
            'warnings' => array_merge($normalized['warnings'], $quotes->isEmpty() ? ['No carrier quotes found for the selected period.'] : []),
            'definitions' => app(KpiDefinitionService::class)->definitions(),
        ];
    }

    private function percentage(int|float $value, int|float $total): float
    {
        return $total > 0 ? round(($value / $total) * 100, 2) : 0.0;
    }

    private function status(mixed $status): string
    {
        return $status instanceof \BackedEnum ? $status->value : (string) $status;
    }
}
