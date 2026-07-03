<?php

namespace App\Services\Forms;

use App\Models\FormTemplate;
use Illuminate\Support\Facades\Validator;

class AiEmailFormExtractionValidationService
{
    public function __construct(private readonly FormFieldNormalizationService $normalizationService) {}

    /**
     * @param  array<string, mixed>  $aiOutput
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function validate(FormTemplate $template, array $aiOutput, array $context = [], array $options = []): array
    {
        $template->loadMissing('fields');
        $errors = [];
        $warnings = [];
        $fieldResults = [];
        $fieldsOutput = $aiOutput['fields'] ?? null;

        if (! is_array($fieldsOutput)) {
            $errors[] = 'invalid_shape_fields';
            $fieldsOutput = [];
        }

        if (! array_key_exists('form_type', $aiOutput) || ! is_string($aiOutput['form_type'])) {
            $errors[] = 'invalid_shape_form_type';
        }

        if (! is_numeric($aiOutput['overall_confidence'] ?? null)) {
            $errors[] = 'invalid_shape_overall_confidence';
        }

        $contextType = $template->context_type instanceof \BackedEnum ? $template->context_type->value : (string) $template->context_type;

        if (($aiOutput['form_type'] ?? $contextType) !== $contextType) {
            $warnings[] = 'form_type_mismatch';
        }

        $knownFieldKeys = $template->fields->pluck('field_key')->all();
        $ignoredUnknownFields = array_values(array_diff(array_keys($fieldsOutput), $knownFieldKeys));

        foreach ($ignoredUnknownFields as $fieldKey) {
            $warnings[] = 'unknown_ai_field_'.$fieldKey;
        }

        foreach ($template->fields as $field) {
            $fieldType = $field->field_type instanceof \BackedEnum ? $field->field_type->value : (string) $field->field_type;
            $fieldOutput = is_array($fieldsOutput[$field->field_key] ?? null) ? $fieldsOutput[$field->field_key] : [];
            $exists = $fieldOutput !== [];
            $extractedValue = $fieldOutput['value'] ?? null;
            $normalization = $this->normalizationService->normalizeByFieldType(
                $fieldType,
                $fieldOutput['normalized_value'] ?? $extractedValue,
            );
            $confidence = $this->confidence($fieldOutput['confidence'] ?? 0);
            $fieldErrors = [];
            $fieldWarnings = [];

            if (! $exists && $field->is_required) {
                $fieldErrors[] = 'required_field_missing';
            }

            if ($field->is_required && ($normalization['value'] === null || $normalization['value'] === '')) {
                $fieldErrors[] = 'required_field_missing';
            }

            if (! $normalization['success']) {
                $fieldErrors[] = $normalization['error'] ?? 'normalization_failed';
            }

            if ($normalization['warning'] !== null) {
                $fieldWarnings[] = $normalization['warning'];
            }

            if ($confidence > 0 && $confidence < $this->thresholdFor($fieldType, $field->is_required)) {
                $fieldWarnings[] = 'low_confidence';
            }

            if (($fieldOutput['warning'] ?? null) !== null) {
                $fieldWarnings[] = (string) $fieldOutput['warning'];
            }

            $fieldWarnings = array_merge($fieldWarnings, $this->contextWarnings($field->field_key, $fieldType, $normalization['value'], $context));
            $ruleErrors = $this->ruleErrors((array) ($field->validation_rules_json ?? []), $normalization['value']);

            $fieldErrors = array_values(array_unique(array_merge($fieldErrors, $ruleErrors)));
            $fieldWarnings = array_values(array_unique($fieldWarnings));
            $requiresReview = $fieldErrors !== [] || $fieldWarnings !== [];

            $fieldResults[$field->field_key] = [
                'exists' => $exists,
                'extracted_value' => $extractedValue,
                'normalized_value' => $normalization['value'],
                'confidence' => $confidence,
                'source_excerpt' => $fieldOutput['source_excerpt'] ?? null,
                'requires_review' => $requiresReview,
                'review_reason' => $requiresReview ? implode('; ', array_merge($fieldErrors, $fieldWarnings)) : null,
                'errors' => $fieldErrors,
                'warnings' => $fieldWarnings,
            ];
        }

        $overallConfidence = $this->confidence($aiOutput['overall_confidence'] ?? 0);

        if ($overallConfidence < $this->configThreshold('overall_min_confidence', 0.80)) {
            $warnings[] = 'overall_low_confidence';
        }

        if (($aiOutput['requires_human_review'] ?? false) === true) {
            $warnings[] = (string) ($aiOutput['human_review_reason'] ?? 'extractor_requested_human_review');
        }

        $warnings = array_values(array_unique(array_filter(array_merge(
            $warnings,
            is_array($aiOutput['warnings'] ?? null) ? $aiOutput['warnings'] : [],
        ))));

        $fieldRequiresReview = collect($fieldResults)->contains(fn (array $result): bool => $result['requires_review'] === true);
        $status = $errors !== []
            ? 'invalid'
            : (($warnings !== [] || $fieldRequiresReview) ? 'needs_review' : 'valid');

        return [
            'status' => $status,
            'requires_human_review' => $status !== 'valid',
            'confidence' => $overallConfidence,
            'overall_confidence' => $overallConfidence,
            'errors' => array_values(array_unique($errors)),
            'warnings' => $warnings,
            'field_results' => $fieldResults,
            'ignored_unknown_fields' => $ignoredUnknownFields,
        ];
    }

    private function confidence(mixed $confidence): float
    {
        $value = is_numeric($confidence) ? (float) $confidence : 0.0;

        return $value > 1.0 ? round($value / 100, 4) : round($value, 4);
    }

    private function thresholdFor(string $fieldType, bool $required): float
    {
        return match ($fieldType) {
            'date' => $this->configThreshold('date_field_min_confidence', 0.90),
            'number', 'decimal' => $this->configThreshold('quantity_field_min_confidence', 0.90),
            'sku' => $this->configThreshold('sku_field_min_confidence', 0.90),
            'currency' => $this->configThreshold('currency_field_min_confidence', 0.85),
            default => $required
                ? $this->configThreshold('required_field_min_confidence', 0.85)
                : $this->configThreshold('overall_min_confidence', 0.80),
        };
    }

    private function configThreshold(string $key, float $default): float
    {
        return (float) config('supply.form_autofill.'.$key, $default);
    }

    /**
     * @param  array<string, mixed>  $context
     * @return list<string>
     */
    private function contextWarnings(string $fieldKey, string $fieldType, mixed $value, array $context): array
    {
        $warnings = [];

        if ($fieldType === 'sku' && $value !== null && ! $this->knownSku((string) $value, $context)) {
            $warnings[] = 'unknown_sku';
        }

        if ($fieldKey === 'confirmed_quantity' && $value !== null && $this->quantityMismatch((float) $value, $context)) {
            $warnings[] = 'quantity_mismatch';
        }

        if ($fieldKey === 'carrier_name' && $value !== null && $this->knownCarriers($context) !== [] && ! $this->knownCarrier((string) $value, $context)) {
            $warnings[] = 'unknown_carrier';
        }

        return $warnings;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function knownSku(string $sku, array $context): bool
    {
        foreach (array_merge($context['expected_items'] ?? [], $context['known_products'] ?? []) as $item) {
            if (! is_array($item)) {
                continue;
            }

            foreach (['sku', 'manufacturer_sku', 'supplier_sku'] as $key) {
                if (strtoupper((string) ($item[$key] ?? '')) === strtoupper($sku)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function quantityMismatch(float $value, array $context): bool
    {
        $expectedItems = $context['expected_items'] ?? [];

        if (! is_array($expectedItems) || $expectedItems === []) {
            return false;
        }

        $expected = count($expectedItems) === 1
            ? (float) ($expectedItems[0]['ordered_quantity'] ?? 0)
            : array_sum(array_map(fn (mixed $item): float => is_array($item) ? (float) ($item['ordered_quantity'] ?? 0) : 0.0, $expectedItems));

        return abs($value - $expected) > 0.0001;
    }

    /**
     * @param  array<string, mixed>  $context
     * @return list<array<string, mixed>>
     */
    private function knownCarriers(array $context): array
    {
        return is_array($context['known_carriers'] ?? null) ? $context['known_carriers'] : [];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function knownCarrier(string $carrierName, array $context): bool
    {
        foreach ($this->knownCarriers($context) as $carrier) {
            if (strtolower((string) ($carrier['name'] ?? '')) === strtolower($carrierName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int|string, mixed>  $rules
     * @return list<string>
     */
    private function ruleErrors(array $rules, mixed $value): array
    {
        $stringRules = array_values(array_filter($rules, fn (mixed $rule): bool => is_string($rule)));

        if ($stringRules === []) {
            return [];
        }

        $validator = Validator::make(['value' => $value], ['value' => $stringRules]);

        return $validator->fails() ? ['validation_rule_failed'] : [];
    }
}
