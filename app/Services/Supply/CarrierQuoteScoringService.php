<?php

namespace App\Services\Supply;

use Carbon\Carbon;
use Throwable;

class CarrierQuoteScoringService
{
    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public function score(array $input, array $config = []): array
    {
        $config = array_replace($this->defaultConfig(), $config);
        $warnings = [];
        $penalties = [];
        $requiresHumanReview = false;

        $price = is_numeric($input['price'] ?? null) ? (float) $input['price'] : null;
        $priceScore = $price === null ? 0.0 : max(0.0, 100.0 - ($price / 10.0));

        if ($price === null) {
            $warnings[] = 'missing_price';
            $penalties[] = [
                'type' => 'penalty_missing_price',
                'points' => (float) $config['penalty_missing_price'],
            ];
        }

        $pickup = $this->date($input['pickup_date'] ?? null);
        $delivery = $this->date($input['delivery_date'] ?? null);
        $requiredPickup = $this->date($input['required_pickup_date'] ?? null);
        $requiredDelivery = $this->date($input['required_delivery_date'] ?? null);

        $pickupScore = $this->dateScore($pickup, $requiredPickup);
        $deliveryScore = $this->dateScore($delivery, $requiredDelivery);

        if ($pickup === null) {
            $warnings[] = 'missing_pickup_date';
            $requiresHumanReview = true;
            $penalties[] = [
                'type' => 'penalty_missing_date',
                'field' => 'pickup_date',
                'points' => (float) $config['penalty_missing_date'],
            ];
        } elseif ($requiredPickup instanceof Carbon && $pickup->gt($requiredPickup)) {
            $daysLate = $requiredPickup->diffInDays($pickup);
            $points = $daysLate * (float) $config['penalty_late_pickup'];
            $warnings[] = 'late_pickup';
            $penalties[] = [
                'type' => 'penalty_late_pickup',
                'field' => 'pickup_date',
                'days_late' => $daysLate,
                'points' => $points,
            ];
        }

        if ($delivery === null) {
            $warnings[] = 'missing_delivery_date';
            $requiresHumanReview = true;
            $penalties[] = [
                'type' => 'penalty_missing_date',
                'field' => 'delivery_date',
                'points' => (float) $config['penalty_missing_date'],
            ];
        } elseif ($requiredDelivery instanceof Carbon && $delivery->gt($requiredDelivery)) {
            $daysLate = $requiredDelivery->diffInDays($delivery);
            $points = $daysLate * (float) $config['penalty_late_delivery'];
            $warnings[] = 'late_delivery';
            $penalties[] = [
                'type' => 'penalty_late_delivery',
                'field' => 'delivery_date',
                'days_late' => $daysLate,
                'points' => $points,
            ];
        }

        $reliabilityScore = is_numeric($input['reliability_score'] ?? null)
            ? min(100.0, max(0.0, (float) $input['reliability_score']))
            : 50.0;

        if (! is_numeric($input['reliability_score'] ?? null)) {
            $warnings[] = 'missing_reliability_score';
        }

        $weightedScore = ($priceScore * (float) $config['price_weight'])
            + ($deliveryScore * (float) $config['delivery_date_weight'])
            + ($pickupScore * (float) $config['pickup_date_weight'])
            + ($reliabilityScore * (float) $config['reliability_weight']);
        $penaltyTotal = collect($penalties)->sum(fn (array $penalty): float => (float) ($penalty['points'] ?? 0));
        $calculatedScore = max(0.0, round($weightedScore - $penaltyTotal, 3));

        return [
            'calculated_score' => $calculatedScore,
            'requires_human_review' => $requiresHumanReview,
            'warnings' => array_values(array_unique($warnings)),
            'explanation' => [
                'config' => $config,
                'component_scores' => [
                    'price' => round($priceScore, 3),
                    'pickup_date' => round($pickupScore, 3),
                    'delivery_date' => round($deliveryScore, 3),
                    'reliability' => round($reliabilityScore, 3),
                ],
                'weighted_score_before_penalties' => round($weightedScore, 3),
                'penalties' => $penalties,
                'penalty_total' => round($penaltyTotal, 3),
                'warnings' => array_values(array_unique($warnings)),
                'final_score' => $calculatedScore,
            ],
        ];
    }

    /**
     * @return array<string, float>
     */
    private function defaultConfig(): array
    {
        return [
            'price_weight' => 0.35,
            'delivery_date_weight' => 0.25,
            'pickup_date_weight' => 0.15,
            'reliability_weight' => 0.25,
            'penalty_late_pickup' => 8.0,
            'penalty_late_delivery' => 15.0,
            'penalty_missing_price' => 25.0,
            'penalty_missing_date' => 35.0,
        ];
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

    private function dateScore(?Carbon $date, ?Carbon $requiredDate): float
    {
        if (! $date instanceof Carbon) {
            return 0.0;
        }

        if (! $requiredDate instanceof Carbon) {
            return 100.0;
        }

        if ($date->lte($requiredDate)) {
            return 100.0;
        }

        return max(0.0, 100.0 - ($requiredDate->diffInDays($date) * 10.0));
    }
}
