<?php

namespace App\Services\AI\Forms;

use App\Contracts\AI\AiEmailFormExtractorInterface;
use Carbon\Carbon;
use Throwable;

class RuleBasedAiEmailFormExtractor implements AiEmailFormExtractorInterface
{
    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function extract(array $input): array
    {
        $text = trim(((string) ($input['email']['subject'] ?? '')).' '.((string) ($input['email']['body_text'] ?? '')));
        $fields = [];
        $warnings = [];

        foreach ($input['fields'] ?? [] as $field) {
            if (! is_array($field)) {
                continue;
            }

            $fieldKey = (string) ($field['field_key'] ?? '');
            $fieldType = (string) ($field['field_type'] ?? 'text');

            if ($fieldKey === '') {
                continue;
            }

            $suggestion = $this->extractField($fieldKey, $fieldType, $text, $input);

            if ($suggestion !== null) {
                $fields[$fieldKey] = $suggestion;
            }
        }

        $confidences = collect($fields)
            ->map(fn (array $field): float => (float) ($field['confidence'] ?? 0.0))
            ->values();

        foreach ($fields as $field) {
            if (($field['warning'] ?? null) !== null) {
                $warnings[] = $field['warning'];
            }
        }

        return [
            'form_type' => $input['template']['context_type'] ?? 'custom_email_form',
            'overall_confidence' => $confidences->isEmpty() ? 0.0 : round($confidences->average(), 4),
            'fields' => $fields,
            'warnings' => array_values(array_unique(array_filter($warnings))),
            'requires_human_review' => true,
            'human_review_reason' => 'rule_based_extractor_requires_review',
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>|null
     */
    private function extractField(string $fieldKey, string $fieldType, string $text, array $input): ?array
    {
        return match ($fieldKey) {
            'supplier_order_number' => $this->orderNumber($text),
            'supplier_reference' => $this->supplierReference($text),
            'sku' => $this->sku($text),
            'confirmed_quantity' => $this->quantity($text),
            'ready_date', 'shipping_date', 'expected_arrival_date', 'pickup_date', 'delivery_date' => $this->date($text),
            'carrier_name' => $this->carrierName($text, $input),
            'price', 'transport_price' => $this->price($text),
            'currency' => $this->currency($text),
            'notes', 'conditions' => $this->excerptField($text),
            default => $this->genericByType($fieldType, $text),
        };
    }

    /**
     * @return array<string, mixed>|null
     */
    private function orderNumber(string $text): ?array
    {
        if (preg_match('/\bPO-\d{8}-\d+\b/i', $text, $match) || preg_match('/\bPO-[A-Z0-9-]+\b/i', $text, $match)) {
            return $this->result($match[0], strtoupper($match[0]), 0.95, $match[0]);
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function supplierReference(string $text): ?array
    {
        if (preg_match('/\b(?:confirmation\s+(?:no\.?|number)|ref(?:erence)?)\s*[:#-]?\s*([A-Z0-9-]+)/i', $text, $match)) {
            return $this->result($match[1], strtoupper($match[1]), 0.85, $match[0]);
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function sku(string $text): ?array
    {
        preg_match_all('/\b[A-Z]{2,5}-\d{2,6}\b/i', $text, $matches);
        $matches = array_values(array_unique($matches[0] ?? []));

        if ($matches === []) {
            return null;
        }

        return $this->result(
            $matches[0],
            strtoupper($matches[0]),
            0.95,
            $matches[0],
            count($matches) > 1 ? 'multiple_skus_found' : null,
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function quantity(string $text): ?array
    {
        $patterns = [
            '/\bconfirmed\s+([0-9][0-9\s,.]*)\b/i',
            '/\bquantity\s+([0-9][0-9\s,.]*)\b/i',
            '/\bqty\.?\s+([0-9][0-9\s,.]*)\b/i',
            '/\b([0-9][0-9\s,.]*)\s*(?:pcs|units?|vnt)\b/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $match)) {
                return $this->result($match[1], $this->number($match[1]), 0.85, $match[0]);
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function date(string $text): ?array
    {
        $patterns = [
            '/\b\d{4}-\d{2}-\d{2}\b/',
            '/\b\d{1,2}\.\d{1,2}\.\d{4}\b/',
            '/\b\d{1,2}\/\d{1,2}\/\d{4}\b/',
            '/\b\d{1,2}\s+(?:Jan|January|Feb|February|Mar|March|Apr|April|May|Jun|June|Jul|July|Aug|August|Sep|September|Oct|October|Nov|November|Dec|December)\s+\d{4}\b/i',
        ];

        $found = [];

        foreach ($patterns as $pattern) {
            preg_match_all($pattern, $text, $matches);
            $found = array_merge($found, $matches[0] ?? []);
        }

        $found = array_values(array_unique($found));

        if ($found === []) {
            return null;
        }

        return $this->result(
            $found[0],
            $this->dateValue($found[0]),
            str_contains($found[0], '-') ? 0.95 : 0.85,
            $found[0],
            count($found) > 1 ? 'multiple_dates_found' : null,
        );
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>|null
     */
    private function carrierName(string $text, array $input): ?array
    {
        foreach ($input['context']['known_carriers'] ?? [] as $carrier) {
            if (is_array($carrier) && isset($carrier['name']) && str_contains(strtolower($text), strtolower((string) $carrier['name']))) {
                return $this->result($carrier['name'], $carrier['name'], 0.90, (string) $carrier['name']);
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function price(string $text): ?array
    {
        if (preg_match('/\b(?:EUR|USD|GBP|PLN)\s*([0-9][0-9\s,.]*)\b/i', $text, $match)
            || preg_match('/\b([0-9][0-9\s,.]*)\s*(?:EUR|USD|GBP|PLN)\b/i', $text, $match)
            || preg_match('/\b(?:price|cost)\s*[:#-]?\s*([0-9][0-9\s,.]*)\b/i', $text, $match)) {
            return $this->result($match[1], $this->number($match[1]), 0.85, $match[0]);
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function currency(string $text): ?array
    {
        if (preg_match('/\b(EUR|USD|GBP|PLN)\b/i', $text, $match)) {
            return $this->result($match[1], strtoupper($match[1]), 0.95, $match[0]);
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function excerptField(string $text): ?array
    {
        $excerpt = trim(mb_substr($text, 0, 300));

        return $excerpt === '' ? null : $this->result($excerpt, $excerpt, 0.65, mb_substr($excerpt, 0, 120));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function genericByType(string $fieldType, string $text): ?array
    {
        return match ($fieldType) {
            'date' => $this->date($text),
            'decimal', 'number' => $this->quantity($text) ?? $this->price($text),
            'currency' => $this->currency($text),
            'sku' => $this->sku($text),
            'textarea' => $this->excerptField($text),
            default => null,
        };
    }

    private function number(string $value): ?float
    {
        $clean = preg_replace('/[^0-9,.-]/', '', str_replace(' ', '', $value));
        $clean = str_replace(',', '.', (string) $clean);

        return is_numeric($clean) ? (float) $clean : null;
    }

    private function dateValue(string $value): ?string
    {
        try {
            return Carbon::parse($value)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function result(mixed $value, mixed $normalized, float $confidence, string $sourceExcerpt, ?string $warning = null): array
    {
        return [
            'value' => $value,
            'confidence' => $confidence,
            'source_excerpt' => mb_substr($sourceExcerpt, 0, 500),
            'normalized_value' => $normalized,
            'warning' => $warning,
        ];
    }
}
