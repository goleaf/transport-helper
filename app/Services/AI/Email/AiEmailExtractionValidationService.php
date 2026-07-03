<?php

namespace App\Services\AI\Email;

use Illuminate\Support\Carbon;
use Throwable;

class AiEmailExtractionValidationService
{
    private const REVIEW_EMAIL_TYPES = [
        'supplier_confirmation',
        'date_update',
        'quantity_mismatch',
    ];

    /**
     * @param  array<string, mixed>  $output
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function validate(array $output, array $context = []): array
    {
        $errors = [];
        $warnings = [];
        $discrepancies = [];
        $normalized = $this->normalizeOutput($output);

        foreach (['email_type', 'confirmed_items', 'dates', 'carrier_quote', 'confidence'] as $key) {
            if (! array_key_exists($key, $output)) {
                $errors[] = 'missing_'.$key;
            }
        }

        if (! in_array($normalized['email_type'], $this->allowedEmailTypes(), true)) {
            $errors[] = 'invalid_email_type';
        }

        if ($normalized['confidence'] < $this->minimumConfidence()) {
            $warnings[] = 'low_confidence';
        }

        if ($normalized['email_type'] === 'unclear') {
            $warnings[] = 'unclear_email_type';
        }

        if (($context['supplier'] ?? null) === null && ($context['supplier_id'] ?? null) === null) {
            $warnings[] = 'unknown_supplier';
        }

        if (in_array($normalized['email_type'], self::REVIEW_EMAIL_TYPES, true)
            && ($context['supplier_order'] ?? null) === null
            && ($context['supplier_order_id'] ?? null) === null) {
            $warnings[] = 'unknown_supplier_order';
        }

        if ($normalized['email_type'] === 'supplier_confirmation' && $normalized['confirmed_items'] === []) {
            $warnings[] = 'missing_confirmed_items';
        }

        [$itemWarnings, $itemDiscrepancies] = $this->validateConfirmedItems($normalized['confirmed_items'], $context);
        $warnings = array_merge($warnings, $itemWarnings);
        $discrepancies = array_merge($discrepancies, $itemDiscrepancies);

        $warnings = array_merge($warnings, $this->validateDates($normalized['dates']));

        if ($normalized['email_type'] === 'transport_quote') {
            if (($normalized['carrier_quote']['price'] ?? null) === null || ($normalized['carrier_quote']['delivery_date'] ?? $normalized['carrier_quote']['date'] ?? null) === null) {
                $warnings[] = 'transport_quote_missing_price_or_date';
            }
        }

        $validShape = $errors === [];
        $requiresReview = $this->requiresHumanReview($normalized, $warnings, $errors);
        $reviewReason = implode('; ', array_values(array_unique(array_merge($errors, $warnings))));

        return [
            'valid_shape' => $validShape,
            'status' => $validShape ? ($requiresReview ? 'needs_review' : 'accepted_candidate') : 'invalid',
            'requires_human_review' => $requiresReview,
            'review_reason' => $reviewReason === '' ? null : $reviewReason,
            'errors' => array_values(array_unique($errors)),
            'warnings' => array_values(array_unique($warnings)),
            'normalized_output' => $normalized,
            'discrepancies' => $discrepancies,
        ];
    }

    /**
     * @return list<string>
     */
    private function allowedEmailTypes(): array
    {
        return [
            'supplier_confirmation',
            'date_update',
            'quantity_mismatch',
            'invoice_or_proforma',
            'transport_quote',
            'generic_reply',
            'unclear',
        ];
    }

    /**
     * @param  array<string, mixed>  $output
     * @return array<string, mixed>
     */
    private function normalizeOutput(array $output): array
    {
        return [
            'email_type' => is_string($output['email_type'] ?? null) ? $output['email_type'] : 'unclear',
            'supplier_order_number' => $output['supplier_order_number'] ?? null,
            'supplier_reference' => $output['supplier_reference'] ?? null,
            'confirmed_items' => is_array($output['confirmed_items'] ?? null) ? array_values($output['confirmed_items']) : [],
            'dates' => is_array($output['dates'] ?? null) ? $output['dates'] : [],
            'carrier_quote' => is_array($output['carrier_quote'] ?? null) ? $output['carrier_quote'] : [],
            'discrepancies' => is_array($output['discrepancies'] ?? null) ? $output['discrepancies'] : [],
            'questions_to_supplier' => is_array($output['questions_to_supplier'] ?? null) ? $output['questions_to_supplier'] : [],
            'confidence' => $this->normalizeConfidence($output['confidence'] ?? 0),
            'requires_human_review' => (bool) ($output['requires_human_review'] ?? true),
            'human_review_reason' => $output['human_review_reason'] ?? null,
        ];
    }

    private function normalizeConfidence(mixed $confidence): float
    {
        $value = is_numeric($confidence) ? (float) $confidence : 0.0;

        return $value > 1 ? $value / 100 : max(0.0, min(1.0, $value));
    }

    private function minimumConfidence(): float
    {
        return app()->bound('config')
            ? (float) config('supply.ai.email_extraction_min_confidence', 0.80)
            : 0.80;
    }

    /**
     * @param  list<array<string, mixed>>  $confirmedItems
     * @param  array<string, mixed>  $context
     * @return array{0:list<string>,1:list<array<string,mixed>>}
     */
    private function validateConfirmedItems(array $confirmedItems, array $context): array
    {
        $warnings = [];
        $discrepancies = [];
        $expectedItems = is_array($context['expected_items'] ?? null) ? $context['expected_items'] : [];

        foreach ($confirmedItems as $confirmedItem) {
            if (! is_array($confirmedItem)) {
                $warnings[] = 'confirmed_item_invalid_shape';

                continue;
            }

            $matchedItem = $this->matchExpectedItem($confirmedItem, $expectedItems);

            if ($matchedItem === null) {
                $warnings[] = 'unknown_sku';

                continue;
            }

            $confirmedQuantity = $confirmedItem['confirmed_quantity'] ?? $confirmedItem['quantity'] ?? null;
            $orderedQuantity = $matchedItem['ordered_quantity'] ?? null;

            if (is_numeric($confirmedQuantity) && is_numeric($orderedQuantity) && abs((float) $confirmedQuantity - (float) $orderedQuantity) > 0.0001) {
                $warnings[] = 'quantity_mismatch';
                $discrepancies[] = [
                    'type' => 'quantity_mismatch',
                    'sku' => $matchedItem['sku'] ?? $confirmedItem['sku'] ?? null,
                    'ordered_quantity' => (float) $orderedQuantity,
                    'confirmed_quantity' => (float) $confirmedQuantity,
                ];
            }
        }

        return [$warnings, $discrepancies];
    }

    /**
     * @param  array<string, mixed>  $confirmedItem
     * @param  list<array<string, mixed>>  $expectedItems
     * @return array<string, mixed>|null
     */
    private function matchExpectedItem(array $confirmedItem, array $expectedItems): ?array
    {
        foreach ($expectedItems as $expectedItem) {
            if (! is_array($expectedItem)) {
                continue;
            }

            foreach (['sku', 'manufacturer_sku', 'supplier_sku'] as $key) {
                $confirmed = $confirmedItem[$key] ?? null;
                $expected = $expectedItem[$key] ?? null;

                if (is_string($confirmed) && $confirmed !== '' && is_string($expected) && strcasecmp($confirmed, $expected) === 0) {
                    return $expectedItem;
                }
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $dates
     * @return list<string>
     */
    private function validateDates(array $dates): array
    {
        $warnings = [];

        foreach ($dates as $date) {
            if ($date === null || $date === '') {
                continue;
            }

            if (! is_string($date) || str_contains($date, '?') || in_array(strtolower($date), ['unknown', 'tbd', 'asap'], true)) {
                $warnings[] = 'ambiguous_date';

                continue;
            }

            try {
                Carbon::parse($date);
            } catch (Throwable) {
                $warnings[] = 'invalid_date';
            }
        }

        return $warnings;
    }

    /**
     * @param  array<string, mixed>  $normalized
     * @param  list<string>  $warnings
     * @param  list<string>  $errors
     */
    private function requiresHumanReview(array $normalized, array $warnings, array $errors): bool
    {
        if ($errors !== [] || $warnings !== []) {
            return true;
        }

        if ($this->requiresHumanReviewByDefault()) {
            return true;
        }

        return (bool) $normalized['requires_human_review'];
    }

    private function requiresHumanReviewByDefault(): bool
    {
        return app()->bound('config')
            ? (bool) config('supply.ai.email_extraction_requires_human_review_by_default', true)
            : true;
    }
}
