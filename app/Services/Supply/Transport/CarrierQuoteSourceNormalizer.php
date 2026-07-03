<?php

namespace App\Services\Supply\Transport;

use App\Enums\CarrierQuoteSourceType;
use App\Models\AiEmailExtraction;
use App\Models\FormAutofillRun;
use Carbon\Carbon;
use Throwable;

class CarrierQuoteSourceNormalizer
{
    /**
     * @param  array<string, mixed>  $manualData
     * @return array<string, mixed>
     */
    public function fromManual(array $manualData): array
    {
        return $this->normalize([
            'source_type' => CarrierQuoteSourceType::Manual->value,
            'source_id' => $manualData['source_id'] ?? null,
            'supplier_order_id' => $manualData['supplier_order_id'] ?? null,
            'carrier_id' => $manualData['carrier_id'] ?? null,
            'carrier_name' => $manualData['carrier_name'] ?? null,
            'price' => $manualData['price'] ?? null,
            'currency' => $manualData['currency'] ?? null,
            'pickup_date' => $manualData['pickup_date'] ?? null,
            'delivery_date' => $manualData['delivery_date'] ?? null,
            'transit_days' => $manualData['transit_days'] ?? null,
            'conditions' => $manualData['conditions'] ?? null,
            'reliability_score' => $manualData['reliability_score'] ?? null,
            'confidence' => $manualData['confidence'] ?? null,
            'source_excerpt' => $manualData['source_excerpt'] ?? null,
            'warnings' => [],
            'raw' => $manualData,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function fromAiExtraction(AiEmailExtraction $extraction): array
    {
        $extraction->loadMissing('emailMessage');
        $output = is_array($extraction->output_json) ? $extraction->output_json : [];
        $carrierQuote = is_array($output['carrier_quote'] ?? null) ? $output['carrier_quote'] : $output;

        return $this->normalize([
            'source_type' => CarrierQuoteSourceType::AiEmailExtraction->value,
            'source_id' => $extraction->id,
            'supplier_order_id' => $extraction->emailMessage?->related_supplier_order_id ?? $output['supplier_order_id'] ?? null,
            'supplier_order_number' => $output['supplier_order_number'] ?? null,
            'carrier_id' => $carrierQuote['carrier_id'] ?? null,
            'carrier_name' => $carrierQuote['carrier_name'] ?? $carrierQuote['carrier'] ?? null,
            'price' => $carrierQuote['price'] ?? null,
            'currency' => $carrierQuote['currency'] ?? null,
            'pickup_date' => $carrierQuote['pickup_date'] ?? null,
            'delivery_date' => $carrierQuote['delivery_date'] ?? null,
            'transit_days' => $carrierQuote['transit_days'] ?? null,
            'conditions' => $carrierQuote['conditions'] ?? null,
            'reliability_score' => $carrierQuote['reliability_score'] ?? null,
            'confidence' => $carrierQuote['confidence'] ?? $extraction->confidence,
            'source_excerpt' => $carrierQuote['source_excerpt'] ?? null,
            'warnings' => array_values(array_filter((array) ($carrierQuote['warnings'] ?? $output['warnings'] ?? []))),
            'raw' => $output,
            'email_message_id' => $extraction->email_message_id,
            'created_from_ai_extraction_id' => $extraction->id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function fromFormAutofillRun(FormAutofillRun $run): array
    {
        $run->loadMissing(['emailMessage', 'fieldValues']);
        $values = $run->fieldValues->mapWithKeys(fn ($field): array => [$field->field_key => $this->fieldValue($field->final_value)])->all();
        $sourceExcerpt = $run->fieldValues->pluck('source_excerpt')->filter()->first();

        return $this->normalize([
            'source_type' => CarrierQuoteSourceType::FormAutofillRun->value,
            'source_id' => $run->id,
            'supplier_order_id' => $run->emailMessage?->related_supplier_order_id ?? $values['supplier_order_id'] ?? null,
            'supplier_order_number' => $values['supplier_order_number'] ?? null,
            'carrier_id' => $values['carrier_id'] ?? null,
            'carrier_name' => $values['carrier_name'] ?? null,
            'price' => $values['price'] ?? null,
            'currency' => $values['currency'] ?? null,
            'pickup_date' => $values['pickup_date'] ?? null,
            'delivery_date' => $values['delivery_date'] ?? null,
            'transit_days' => $values['transit_days'] ?? null,
            'conditions' => $values['conditions'] ?? $values['notes'] ?? null,
            'reliability_score' => $values['reliability_score'] ?? null,
            'confidence' => $run->confidence,
            'source_excerpt' => $sourceExcerpt,
            'warnings' => is_array($run->warnings_json) ? $run->warnings_json : [],
            'raw' => $values,
            'email_message_id' => $run->email_message_id,
            'created_from_form_autofill_run_id' => $run->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $quote
     * @return array<string, mixed>
     */
    private function normalize(array $quote): array
    {
        return [
            'source_type' => $quote['source_type'] ?? null,
            'source_id' => $quote['source_id'] ?? null,
            'supplier_order_id' => $this->nullableInteger($quote['supplier_order_id'] ?? null),
            'supplier_order_number' => $this->nullableString($quote['supplier_order_number'] ?? null),
            'carrier_id' => $this->nullableInteger($quote['carrier_id'] ?? null),
            'carrier_name' => $this->nullableString($quote['carrier_name'] ?? null),
            'price' => $this->decimal($quote['price'] ?? null),
            'currency' => $this->currency($quote['currency'] ?? null),
            'pickup_date' => $this->date($quote['pickup_date'] ?? null),
            'delivery_date' => $this->date($quote['delivery_date'] ?? null),
            'transit_days' => $this->nullableInteger($quote['transit_days'] ?? null),
            'conditions' => $this->nullableString($quote['conditions'] ?? null),
            'reliability_score' => $this->decimal($quote['reliability_score'] ?? null),
            'confidence' => $this->decimal($quote['confidence'] ?? null),
            'source_excerpt' => $this->nullableString($quote['source_excerpt'] ?? null),
            'warnings' => array_values(array_filter((array) ($quote['warnings'] ?? []))),
            'raw' => $quote['raw'] ?? [],
            'email_message_id' => $this->nullableInteger($quote['email_message_id'] ?? null),
            'created_from_ai_extraction_id' => $this->nullableInteger($quote['created_from_ai_extraction_id'] ?? null),
            'created_from_form_autofill_run_id' => $this->nullableInteger($quote['created_from_form_autofill_run_id'] ?? null),
        ];
    }

    private function fieldValue(mixed $value): mixed
    {
        if (is_array($value) && array_key_exists('value', $value)) {
            return $value['value'];
        }

        return $value;
    }

    private function decimal(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $normalized = str_replace([' ', ','], ['', '.'], (string) $value);
        $normalized = preg_replace('/[^0-9.\-]/', '', $normalized) ?? '';

        return is_numeric($normalized) ? (float) $normalized : null;
    }

    private function currency(mixed $value): ?string
    {
        $value = $this->nullableString($value);

        return $value === null ? null : strtoupper($value);
    }

    private function date(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    private function nullableInteger(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
