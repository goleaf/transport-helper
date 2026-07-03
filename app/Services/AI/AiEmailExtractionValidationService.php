<?php

namespace App\Services\AI;

use App\Models\AiEmailExtraction;
use App\Models\SupplierOrder;
use Illuminate\Support\Carbon;
use Throwable;

class AiEmailExtractionValidationService
{
    private const CONFIDENCE_THRESHOLD = 0.75;

    /**
     * @return array{status:string,reasons:list<string>,confidence:float,output:array<string,mixed>}
     */
    public function validate(AiEmailExtraction $extraction): array
    {
        $output = is_array($extraction->output_json) ? $extraction->output_json : [];
        $reasons = [];

        foreach ($this->requiredKeys() as $key) {
            if (! array_key_exists($key, $output)) {
                $reasons[] = 'invalid_shape_missing_'.$key;
            }
        }

        $confidence = $this->normalizedConfidence($output['confidence'] ?? $extraction->confidence ?? 0);

        if ($confidence < self::CONFIDENCE_THRESHOLD) {
            $reasons[] = 'low_confidence';
        }

        if (($output['requires_human_review'] ?? true) === true) {
            $reasons[] = $output['human_review_reason'] ?? 'ai_requested_human_review';
        }

        $supplierOrder = $this->supplierOrderFor($extraction, $output);

        if ($supplierOrder instanceof SupplierOrder) {
            $reasons = array_merge($reasons, $this->validateConfirmedItems($supplierOrder, $output));
        }

        $reasons = array_merge($reasons, $this->validateDates($output['dates'] ?? []));

        if (($output['discrepancies'] ?? []) !== []) {
            $reasons[] = 'ai_reported_discrepancies';
        }

        $reasons = array_values(array_unique(array_filter($reasons)));

        return [
            'status' => $this->statusFor($reasons),
            'reasons' => $reasons,
            'confidence' => $confidence,
            'output' => $output,
        ];
    }

    /**
     * @return list<string>
     */
    private function requiredKeys(): array
    {
        return [
            'email_type',
            'supplier_order_number',
            'supplier_reference',
            'confirmed_items',
            'dates',
            'carrier_quote',
            'discrepancies',
            'questions_to_supplier',
            'confidence',
            'requires_human_review',
            'human_review_reason',
        ];
    }

    private function normalizedConfidence(mixed $confidence): float
    {
        $value = is_numeric($confidence) ? (float) $confidence : 0.0;

        return $value > 1.0 ? $value / 100.0 : $value;
    }

    private function supplierOrderFor(AiEmailExtraction $extraction, array $output): ?SupplierOrder
    {
        $extraction->loadMissing('emailMessage.relatedSupplierOrder');

        if ($extraction->emailMessage?->relatedSupplierOrder instanceof SupplierOrder) {
            return $extraction->emailMessage->relatedSupplierOrder;
        }

        $orderNumber = $output['supplier_order_number'] ?? null;

        if (! is_string($orderNumber) || $orderNumber === '') {
            return null;
        }

        return SupplierOrder::query()
            ->select(['id', 'company_id', 'order_number'])
            ->where('company_id', $extraction->emailMessage?->company_id)
            ->where('order_number', $orderNumber)
            ->first();
    }

    /**
     * @return list<string>
     */
    private function validateConfirmedItems(SupplierOrder $supplierOrder, array $output): array
    {
        $confirmedItems = $output['confirmed_items'] ?? [];

        if (! is_array($confirmedItems)) {
            return ['confirmed_items_not_array'];
        }

        $supplierOrder->loadMissing('items.product:id,sku,name');
        $orderItemsBySku = $supplierOrder->items->keyBy(fn ($item): string => (string) $item->product?->sku);
        $reasons = [];

        foreach ($confirmedItems as $confirmedItem) {
            if (! is_array($confirmedItem)) {
                $reasons[] = 'confirmed_item_invalid_shape';

                continue;
            }

            $sku = (string) ($confirmedItem['sku'] ?? '');

            if ($sku === '' || ! $orderItemsBySku->has($sku)) {
                $reasons[] = 'unknown_sku';

                continue;
            }

            $confirmedQuantity = $confirmedItem['confirmed_quantity'] ?? $confirmedItem['quantity'] ?? null;

            if (is_numeric($confirmedQuantity)) {
                $orderedQuantity = (float) $orderItemsBySku->get($sku)->ordered_quantity;

                if (abs((float) $confirmedQuantity - $orderedQuantity) > 0.0001) {
                    $reasons[] = 'quantity_mismatch';
                }
            }
        }

        return $reasons;
    }

    /**
     * @return list<string>
     */
    private function validateDates(mixed $dates): array
    {
        if (! is_array($dates)) {
            return ['dates_not_array'];
        }

        $reasons = [];

        foreach ($dates as $date) {
            if ($date === null || $date === '') {
                continue;
            }

            if (is_array($date) || str_contains((string) $date, '?') || in_array((string) $date, ['unknown', 'tbd', 'asap'], true)) {
                $reasons[] = 'date_ambiguity';

                continue;
            }

            try {
                Carbon::parse((string) $date);
            } catch (Throwable) {
                $reasons[] = 'date_ambiguity';
            }
        }

        return $reasons;
    }

    /**
     * @param  list<string>  $reasons
     */
    private function statusFor(array $reasons): string
    {
        if (array_any($reasons, fn (string $reason): bool => str_starts_with($reason, 'invalid_shape'))) {
            return 'rejected';
        }

        return $reasons === [] ? 'accepted' : 'needs_review';
    }
}
