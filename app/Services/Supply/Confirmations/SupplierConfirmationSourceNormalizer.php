<?php

namespace App\Services\Supply\Confirmations;

use App\Models\AiEmailExtraction;
use App\Models\FormAutofillRun;
use Carbon\Carbon;
use Throwable;

class SupplierConfirmationSourceNormalizer
{
    /**
     * @param  array<string, mixed>  $manualData
     * @return array<string, mixed>
     */
    public function fromManual(array $manualData): array
    {
        return $this->normalize($manualData, 'manual', null, null);
    }

    /**
     * @return array<string, mixed>
     */
    public function fromAiExtraction(AiEmailExtraction $extraction): array
    {
        $extraction->loadMissing('emailMessage:id,company_id,related_supplier_order_id');
        $output = is_array($extraction->output_json) ? $extraction->output_json : [];
        $dates = is_array($output['dates'] ?? null) ? $output['dates'] : [];

        $data = [
            'supplier_order_number' => $output['supplier_order_number'] ?? null,
            'supplier_reference' => $output['supplier_reference'] ?? null,
            'confirmation_date' => $dates['confirmation_date'] ?? null,
            'ready_date' => $dates['ready_date'] ?? $dates['ready'] ?? null,
            'shipping_date' => $dates['shipping_date'] ?? $dates['ship_date'] ?? null,
            'expected_arrival_date' => $dates['expected_arrival_date'] ?? $dates['delivery_date'] ?? null,
            'items' => is_array($output['confirmed_items'] ?? null) ? $output['confirmed_items'] : [],
        ];

        return $this->normalize($data, 'ai_email_extraction', $extraction->getKey(), $extraction->email_message_id, $output);
    }

    /**
     * @return array<string, mixed>
     */
    public function fromFormAutofillRun(FormAutofillRun $run): array
    {
        $run->loadMissing('fieldValues');
        $fields = $run->fieldValues->keyBy('field_key');
        $values = $fields->mapWithKeys(fn ($field): array => [$field->field_key => $this->scalarValue($field->final_value)])->all();
        $items = $values['items'] ?? null;

        if (! is_array($items) && (isset($values['sku']) || isset($values['product_id']) || isset($values['confirmed_quantity']))) {
            $items = [[
                'product_id' => $values['product_id'] ?? null,
                'sku' => $values['sku'] ?? null,
                'manufacturer_sku' => $values['manufacturer_sku'] ?? null,
                'supplier_sku' => $values['supplier_sku'] ?? null,
                'confirmed_quantity' => $values['confirmed_quantity'] ?? null,
                'unit' => $values['unit'] ?? null,
                'notes' => $values['notes'] ?? null,
                'source_excerpt' => $fields->get('sku')?->source_excerpt
                    ?? $fields->get('confirmed_quantity')?->source_excerpt,
            ]];
        }

        $data = [
            'supplier_order_number' => $values['supplier_order_number'] ?? null,
            'supplier_reference' => $values['supplier_reference'] ?? null,
            'confirmation_date' => $values['confirmation_date'] ?? null,
            'ready_date' => $values['ready_date'] ?? null,
            'shipping_date' => $values['shipping_date'] ?? null,
            'expected_arrival_date' => $values['expected_arrival_date'] ?? $values['delivery_date'] ?? null,
            'items' => is_array($items) ? $items : [],
        ];

        return $this->normalize($data, 'form_autofill_run', $run->getKey(), $run->email_message_id, [
            'fields' => $values,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>
     */
    private function normalize(array $data, string $sourceType, ?int $sourceId, ?int $emailMessageId, array $raw = []): array
    {
        $warnings = [];
        $dates = [];

        foreach (['confirmation_date', 'ready_date', 'shipping_date', 'expected_arrival_date', 'pickup_date', 'delivery_date'] as $key) {
            $normalized = $this->normalizeDate($data[$key] ?? null);
            $dates[$key] = $normalized['value'];

            if ($normalized['warning'] !== null) {
                $warnings[] = $normalized['warning'].':'.$key;
            }
        }

        $items = collect(is_array($data['items'] ?? null) ? $data['items'] : [])
            ->filter(fn (mixed $item): bool => is_array($item))
            ->map(fn (array $item): array => $this->normalizeItem($item))
            ->values()
            ->all();

        return [
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'email_message_id' => $emailMessageId,
            'supplier_order_number' => $this->nullableString($data['supplier_order_number'] ?? null),
            'supplier_reference' => $this->nullableString($data['supplier_reference'] ?? null),
            'confirmation_date' => $dates['confirmation_date'],
            'ready_date' => $dates['ready_date'],
            'shipping_date' => $dates['shipping_date'],
            'expected_arrival_date' => $dates['expected_arrival_date'],
            'pickup_date' => $dates['pickup_date'],
            'delivery_date' => $dates['delivery_date'],
            'items' => $items,
            'dates' => $dates,
            'warnings' => array_values(array_unique($warnings)),
            'raw' => $raw === [] ? $data : $raw,
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function normalizeItem(array $item): array
    {
        return [
            'product_id' => isset($item['product_id']) && is_numeric($item['product_id']) ? (int) $item['product_id'] : null,
            'sku' => $this->nullableString($item['sku'] ?? null),
            'manufacturer_sku' => $this->nullableString($item['manufacturer_sku'] ?? null),
            'supplier_sku' => $this->nullableString($item['supplier_sku'] ?? null),
            'confirmed_quantity' => $this->normalizeQuantity($item['confirmed_quantity'] ?? $item['quantity'] ?? null),
            'unit' => $this->nullableString($item['unit'] ?? null),
            'notes' => $this->nullableString($item['notes'] ?? null),
            'source_excerpt' => $this->nullableString($item['source_excerpt'] ?? null),
            'source_item' => $item,
        ];
    }

    /**
     * @return array{value:?string,warning:?string}
     */
    private function normalizeDate(mixed $value): array
    {
        $value = $this->scalarValue($value);

        if ($value === null || $value === '') {
            return ['value' => null, 'warning' => null];
        }

        $text = trim((string) $value);

        if ($text === '' || str_contains($text, '?')) {
            return ['value' => null, 'warning' => 'ambiguous_date'];
        }

        try {
            return ['value' => Carbon::parse($text)->toDateString(), 'warning' => null];
        } catch (Throwable) {
            return ['value' => null, 'warning' => 'invalid_date'];
        }
    }

    private function normalizeQuantity(mixed $value): ?float
    {
        $value = $this->scalarValue($value);

        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $number = preg_replace('/[^0-9,.\-]/', '', (string) $value);
        $number = str_replace(',', '.', (string) $number);

        return is_numeric($number) ? (float) $number : null;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = $this->scalarValue($value);

        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    private function scalarValue(mixed $value): mixed
    {
        if (is_array($value) && array_key_exists('value', $value)) {
            return $value['value'];
        }

        return $value;
    }
}
