<?php

namespace App\Services\FormAutofill;

use App\Models\FormTemplate;
use App\Models\SupplierOrder;
use Carbon\Carbon;
use Throwable;

class AiEmailFormExtractionValidationService
{
    public const OVERALL_MINIMUM = 0.80;

    public const REQUIRED_FIELD_MINIMUM = 0.85;

    public const DATE_FIELD_MINIMUM = 0.90;

    public const QUANTITY_FIELD_MINIMUM = 0.90;

    public const SKU_FIELD_MINIMUM = 0.90;

    /**
     * @param  array<string, mixed>  $output
     * @param  array<string, mixed>  $context
     * @return array{status:string,errors:list<string>,warnings:list<string>,field_reviews:array<string,list<string>>,overall_confidence:float}
     */
    public function validate(FormTemplate $template, array $output, array $context = []): array
    {
        $template->loadMissing('fields');
        $errors = [];
        $warnings = [];
        $fieldReviews = [];
        $fieldsOutput = $output['fields'] ?? [];

        if (! is_array($fieldsOutput)) {
            $errors[] = 'invalid_shape_fields';
            $fieldsOutput = [];
        }

        foreach (['form_type', 'overall_confidence', 'fields', 'warnings', 'requires_human_review', 'human_review_reason'] as $key) {
            if (! array_key_exists($key, $output)) {
                $errors[] = 'invalid_shape_missing_'.$key;
            }
        }

        $knownFieldKeys = $template->fields->pluck('field_key')->all();

        foreach (array_keys($fieldsOutput) as $fieldKey) {
            if (! in_array($fieldKey, $knownFieldKeys, true)) {
                $warnings[] = 'unknown_field_'.$fieldKey;
            }
        }

        foreach ($template->fields as $field) {
            $fieldOutput = $fieldsOutput[$field->field_key] ?? null;
            $reasons = [];

            if (! is_array($fieldOutput)) {
                if ($field->is_required) {
                    $reasons[] = 'required_field_missing';
                }

                if ($reasons !== []) {
                    $fieldReviews[$field->field_key] = $reasons;
                }

                continue;
            }

            $value = $fieldOutput['normalized_value'] ?? $fieldOutput['value'] ?? null;
            $confidence = $this->confidence($fieldOutput['confidence'] ?? null);
            $threshold = $this->thresholdFor($field->field_type->value, $field->is_required);

            if ($field->is_required && ($value === null || $value === '')) {
                $reasons[] = 'required_field_missing';
            }

            if ($confidence < $threshold) {
                $reasons[] = 'low_confidence';
            }

            if (($fieldOutput['warning'] ?? null) !== null) {
                $reasons[] = (string) $fieldOutput['warning'];
            }

            if ($field->field_type->value === 'date' && $value !== null && ! $this->isValidDate($value)) {
                $reasons[] = 'date_ambiguity';
            }

            if (in_array($field->field_type->value, ['number', 'decimal'], true) && $value !== null && ! is_numeric($value)) {
                $reasons[] = 'quantity_ambiguity';
            }

            if ($field->field_type->value === 'sku' && $value !== null && ! $this->knownSku((string) $value, $context)) {
                $reasons[] = 'unknown_sku';
            }

            if ($field->field_key === 'confirmed_quantity') {
                $quantityMismatch = $this->quantityMismatch($value, $context);

                if ($quantityMismatch) {
                    $reasons[] = 'quantity_mismatch';
                }
            }

            if ($reasons !== []) {
                $fieldReviews[$field->field_key] = array_values(array_unique($reasons));
            }
        }

        $overallConfidence = $this->confidence($output['overall_confidence'] ?? null);

        if ($overallConfidence < self::OVERALL_MINIMUM) {
            $warnings[] = 'overall_low_confidence';
        }

        if (($output['requires_human_review'] ?? false) === true) {
            $warnings[] = $output['human_review_reason'] ?? 'ai_requested_human_review';
        }

        $warnings = array_values(array_unique(array_filter(array_merge($warnings, is_array($output['warnings'] ?? null) ? $output['warnings'] : []))));
        $status = ($errors === [] && $warnings === [] && $fieldReviews === []) ? 'accepted' : 'needs_review';

        if ($errors !== []) {
            $status = 'needs_review';
        }

        return [
            'status' => $status,
            'errors' => array_values(array_unique($errors)),
            'warnings' => $warnings,
            'field_reviews' => $fieldReviews,
            'overall_confidence' => $overallConfidence,
        ];
    }

    private function confidence(mixed $confidence): float
    {
        $value = is_numeric($confidence) ? (float) $confidence : 0.0;

        return $value > 1.0 ? $value / 100.0 : $value;
    }

    private function thresholdFor(string $fieldType, bool $required): float
    {
        return match ($fieldType) {
            'date' => self::DATE_FIELD_MINIMUM,
            'number', 'decimal' => self::QUANTITY_FIELD_MINIMUM,
            'sku' => self::SKU_FIELD_MINIMUM,
            default => $required ? self::REQUIRED_FIELD_MINIMUM : self::OVERALL_MINIMUM,
        };
    }

    private function isValidDate(mixed $value): bool
    {
        if (is_array($value) || str_contains((string) $value, '?')) {
            return false;
        }

        try {
            Carbon::parse((string) $value);

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function knownSku(string $sku, array $context): bool
    {
        $knownProducts = $context['known_products'] ?? [];

        if (is_array($knownProducts)) {
            foreach ($knownProducts as $product) {
                if (is_array($product) && strtoupper((string) ($product['sku'] ?? '')) === strtoupper($sku)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function quantityMismatch(mixed $value, array $context): bool
    {
        if (! is_numeric($value)) {
            return false;
        }

        $supplierOrder = $context['supplier_order_model'] ?? null;

        if (! $supplierOrder instanceof SupplierOrder) {
            return false;
        }

        $supplierOrder->loadMissing('items');
        $orderedQuantity = $supplierOrder->items->sum(fn ($item): float => (float) $item->ordered_quantity);

        return abs((float) $value - $orderedQuantity) > 0.0001;
    }
}
