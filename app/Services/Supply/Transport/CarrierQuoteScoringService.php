<?php

namespace App\Services\Supply\Transport;

use App\Enums\CarrierQuoteStatus;
use App\Models\CarrierQuote;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Throwable;

class CarrierQuoteScoringService
{
    /**
     * @param  array<string, mixed>  $requirements
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public function score(CarrierQuote $quote, array $requirements = [], array $config = []): array
    {
        $quote->loadMissing('carrier:id,reliability_score');
        $config = array_replace($this->defaultConfig(), $config);
        $warnings = [];
        $penalties = [];
        $subscores = [
            'price' => $this->priceSubscore($quote, $requirements['competing_quotes'] ?? null),
            'delivery_date' => $this->dateSubscore($quote->delivery_date, $requirements['required_delivery_date'] ?? null),
            'pickup_date' => $this->dateSubscore($quote->pickup_date, $requirements['required_pickup_date'] ?? null),
            'reliability' => $this->reliabilitySubscore($quote),
        ];

        if ($quote->price === null) {
            $warnings[] = 'missing_price';
            $penalties[] = ['type' => 'penalty_missing_price', 'points' => (float) $config['penalty_missing_price']];
        }

        if ($quote->delivery_date === null) {
            $warnings[] = 'missing_delivery_date';
            $penalties[] = ['type' => 'penalty_missing_date', 'field' => 'delivery_date', 'points' => (float) $config['penalty_missing_date']];
        }

        $pickupDate = $this->date($quote->pickup_date);
        $deliveryDate = $this->date($quote->delivery_date);

        if ($pickupDate instanceof Carbon && $deliveryDate instanceof Carbon && $deliveryDate->lt($pickupDate)) {
            $warnings[] = 'invalid_date_order';
            $penalties[] = ['type' => 'penalty_invalid_date_order', 'points' => (float) $config['penalty_invalid_date_order']];
        }

        $requiredPickupDate = $this->date($requirements['required_pickup_date'] ?? null);
        $requiredDeliveryDate = $this->date($requirements['required_delivery_date'] ?? null);

        if ($pickupDate instanceof Carbon && $requiredPickupDate instanceof Carbon && $pickupDate->gt($requiredPickupDate)) {
            $daysLate = $requiredPickupDate->diffInDays($pickupDate);
            $warnings[] = 'late_pickup';
            $penalties[] = [
                'type' => 'penalty_late_pickup',
                'days_late' => $daysLate,
                'points' => $daysLate * (float) $config['penalty_late_pickup'],
            ];
        }

        if ($deliveryDate instanceof Carbon && $requiredDeliveryDate instanceof Carbon && $deliveryDate->gt($requiredDeliveryDate)) {
            $daysLate = $requiredDeliveryDate->diffInDays($deliveryDate);
            $warnings[] = 'late_delivery';
            $penalties[] = [
                'type' => 'penalty_late_delivery',
                'days_late' => $daysLate,
                'points' => $daysLate * (float) $config['penalty_late_delivery'],
            ];
        } elseif ($deliveryDate instanceof Carbon && $requiredDeliveryDate instanceof Carbon) {
            $subscores['delivery_date'] = min(100.0, $subscores['delivery_date'] + (float) $config['bonus_on_time_delivery']);
        }

        if ($quote->status === CarrierQuoteStatus::NeedsReview) {
            $warnings[] = 'needs_review_quote';
            $penalties[] = ['type' => 'penalty_needs_review', 'points' => (float) $config['penalty_needs_review']];
        }

        $weightedScore = ($subscores['price'] * (float) $config['price_weight'])
            + ($subscores['delivery_date'] * (float) $config['delivery_date_weight'])
            + ($subscores['pickup_date'] * (float) $config['pickup_date_weight'])
            + ($subscores['reliability'] * (float) $config['reliability_weight']);
        $penaltyTotal = collect($penalties)->sum(fn (array $penalty): float => (float) ($penalty['points'] ?? 0));
        $score = max(0.0, min(100.0, round($weightedScore - $penaltyTotal, 3)));

        return [
            'score' => $score,
            'calculated_score' => $score,
            'subscores' => array_map(fn (float $value): float => round($value, 3), $subscores),
            'weights' => [
                'price' => (float) $config['price_weight'],
                'delivery_date' => (float) $config['delivery_date_weight'],
                'pickup_date' => (float) $config['pickup_date_weight'],
                'reliability' => (float) $config['reliability_weight'],
            ],
            'penalties' => $penalties,
            'warnings' => array_values(array_unique($warnings)),
            'explanation' => [
                'summary' => $this->summary($subscores, $warnings),
                'final' => 'Weighted score = '.$score.'.',
                'subscores' => array_map(fn (float $value): float => round($value, 3), $subscores),
                'weights' => $config,
                'penalties' => $penalties,
                'warnings' => array_values(array_unique($warnings)),
            ],
            'requires_human_review' => in_array('missing_delivery_date', $warnings, true) || in_array('missing_price', $warnings, true),
        ];
    }

    /**
     * @return array<string, float>
     */
    private function defaultConfig(): array
    {
        return [
            'price_weight' => 0.40,
            'delivery_date_weight' => 0.30,
            'pickup_date_weight' => 0.10,
            'reliability_weight' => 0.20,
            'penalty_late_pickup' => 20.0,
            'penalty_late_delivery' => 40.0,
            'penalty_missing_price' => 50.0,
            'penalty_missing_date' => 50.0,
            'penalty_invalid_date_order' => 100.0,
            'penalty_needs_review' => 30.0,
            'bonus_on_time_delivery' => 10.0,
        ];
    }

    private function priceSubscore(CarrierQuote $quote, mixed $competingQuotes): float
    {
        if ($quote->price === null) {
            return 0.0;
        }

        $price = (float) $quote->price;
        $prices = $this->prices($competingQuotes);

        if ($prices->count() < 2) {
            return 70.0;
        }

        $min = (float) $prices->min();
        $max = (float) $prices->max();

        if (abs($max - $min) < 0.0001) {
            return 100.0;
        }

        return max(0.0, min(100.0, 100.0 - ((($price - $min) / ($max - $min)) * 60.0)));
    }

    private function dateSubscore(mixed $date, mixed $requiredDate): float
    {
        $date = $this->date($date);

        if (! $date instanceof Carbon) {
            return 0.0;
        }

        $requiredDate = $this->date($requiredDate);

        if (! $requiredDate instanceof Carbon) {
            return 70.0;
        }

        if ($date->lte($requiredDate)) {
            return 100.0;
        }

        return max(0.0, 100.0 - ($requiredDate->diffInDays($date) * 20.0));
    }

    private function reliabilitySubscore(CarrierQuote $quote): float
    {
        $score = $quote->reliability_score ?? $quote->carrier?->reliability_score;

        if (! is_numeric($score)) {
            return 60.0;
        }

        $score = (float) $score;

        if ($score <= 5.0) {
            return $score * 20.0;
        }

        return max(0.0, min(100.0, $score));
    }

    private function date(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }

    private function prices(mixed $competingQuotes): Collection
    {
        if ($competingQuotes instanceof Collection) {
            return $competingQuotes->pluck('price')->filter(fn (mixed $price): bool => is_numeric($price))->map(fn (mixed $price): float => (float) $price)->values();
        }

        if (is_array($competingQuotes)) {
            return collect($competingQuotes)
                ->map(fn (mixed $quote): mixed => $quote instanceof CarrierQuote ? $quote->price : ($quote['price'] ?? null))
                ->filter(fn (mixed $price): bool => is_numeric($price))
                ->map(fn (mixed $price): float => (float) $price)
                ->values();
        }

        return collect();
    }

    /**
     * @param  array<string, float>  $subscores
     * @param  list<string>  $warnings
     */
    private function summary(array $subscores, array $warnings): string
    {
        if (in_array('late_delivery', $warnings, true)) {
            return 'Price is weighed against a late delivery penalty.';
        }

        if ($subscores['price'] >= 90 && $subscores['delivery_date'] >= 90) {
            return 'Good price and acceptable delivery date.';
        }

        return 'Quote scored by price, dates, reliability and penalties.';
    }
}
