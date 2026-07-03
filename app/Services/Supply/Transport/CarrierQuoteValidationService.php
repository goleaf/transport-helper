<?php

namespace App\Services\Supply\Transport;

use App\Enums\CarrierQuoteStatus;
use App\Models\Carrier;
use Carbon\Carbon;
use Throwable;

class CarrierQuoteValidationService
{
    /**
     * @param  array<string, mixed>  $quote
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function validate(array $quote, array $context = []): array
    {
        $errors = [];
        $warnings = array_values(array_filter((array) ($quote['warnings'] ?? [])));
        $normalized = $quote;
        $companyId = $this->nullableInteger($context['company_id'] ?? $quote['company_id'] ?? null);

        if (empty($normalized['carrier_id']) && filled($normalized['carrier_name'] ?? null) && $companyId !== null) {
            $carrierResult = $this->resolveCarrier($companyId, (string) $normalized['carrier_name']);
            $normalized['carrier_id'] = $carrierResult['carrier_id'];
            $warnings = array_merge($warnings, $carrierResult['warnings']);
            $errors = array_merge($errors, $carrierResult['errors']);
        }

        if (empty($normalized['carrier_id']) && blank($normalized['carrier_name'] ?? null)) {
            $errors[] = 'carrier_required';
        }

        if (empty($normalized['carrier_id']) && ! ($context['allow_unknown_carrier'] ?? false)) {
            $errors[] = 'unknown_carrier';
        } elseif (empty($normalized['carrier_id'])) {
            $warnings[] = 'unknown_carrier';
        }

        if (! array_key_exists('price', $normalized) || $normalized['price'] === null || $normalized['price'] === '') {
            $warnings[] = 'missing_price';
        } elseif (! is_numeric($normalized['price']) || (float) $normalized['price'] < 0) {
            $errors[] = 'invalid_price';
        } elseif ((float) $normalized['price'] === 0.0 && ! ($context['allow_zero_price'] ?? false)) {
            $warnings[] = 'zero_price';
        }

        if (($normalized['price'] ?? null) !== null && blank($normalized['currency'] ?? null)) {
            $warnings[] = 'missing_currency';
        }

        $pickupDate = $this->date($normalized['pickup_date'] ?? null);
        $deliveryDate = $this->date($normalized['delivery_date'] ?? null);
        $requiredPickupDate = $this->date($context['required_pickup_date'] ?? null);
        $requiredDeliveryDate = $this->date($context['required_delivery_date'] ?? null);

        if (($normalized['pickup_date'] ?? null) !== null && ! $pickupDate instanceof Carbon) {
            $errors[] = 'invalid_pickup_date';
        }

        if (($normalized['delivery_date'] ?? null) === null && ! ($context['allow_missing_delivery_date'] ?? false)) {
            $warnings[] = 'missing_delivery_date';
        } elseif (($normalized['delivery_date'] ?? null) !== null && ! $deliveryDate instanceof Carbon) {
            $errors[] = 'invalid_delivery_date';
        }

        if ($pickupDate instanceof Carbon && $deliveryDate instanceof Carbon && $deliveryDate->lt($pickupDate)) {
            $errors[] = 'invalid_date_order';
            $warnings[] = 'invalid_date_order';
        }

        if ($requiredPickupDate instanceof Carbon && $pickupDate instanceof Carbon && $pickupDate->gt($requiredPickupDate)) {
            $warnings[] = 'late_pickup';
        }

        if ($requiredDeliveryDate instanceof Carbon && $deliveryDate instanceof Carbon && $deliveryDate->gt($requiredDeliveryDate)) {
            $warnings[] = 'late_delivery';
        }

        if (($normalized['transit_days'] ?? null) !== null && (! is_numeric($normalized['transit_days']) || (int) $normalized['transit_days'] < 0)) {
            $errors[] = 'invalid_transit_days';
        }

        if (($normalized['confidence'] ?? null) !== null && is_numeric($normalized['confidence']) && (float) $normalized['confidence'] < 0.80) {
            $warnings[] = 'low_confidence_source';
        }

        if (($normalized['source_type'] ?? null) !== 'manual' && $warnings !== []) {
            $warnings[] = 'needs_review_source';
        }

        $warnings = array_values(array_unique($warnings));
        $errors = array_values(array_unique($errors));
        $requiresReview = $warnings !== [] || $errors !== [];

        return [
            'valid' => $errors === [],
            'status' => $requiresReview ? CarrierQuoteStatus::NeedsReview->value : CarrierQuoteStatus::Received->value,
            'errors' => $errors,
            'warnings' => $warnings,
            'normalized' => $normalized,
            'requires_review' => $requiresReview,
        ];
    }

    /**
     * @return array{carrier_id: int|null, warnings: list<string>, errors: list<string>}
     */
    private function resolveCarrier(int $companyId, string $nameOrCode): array
    {
        $matches = Carrier::query()
            ->select(['id', 'name', 'code'])
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->where(function ($query) use ($nameOrCode): void {
                $query->where('name', $nameOrCode)
                    ->orWhere('code', $nameOrCode);
            })
            ->limit(3)
            ->get();

        if ($matches->count() === 1) {
            return ['carrier_id' => (int) $matches->first()->id, 'warnings' => [], 'errors' => []];
        }

        if ($matches->count() > 1) {
            return ['carrier_id' => null, 'warnings' => [], 'errors' => ['ambiguous_carrier']];
        }

        return ['carrier_id' => null, 'warnings' => ['unknown_carrier'], 'errors' => []];
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

    private function nullableInteger(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }
}
