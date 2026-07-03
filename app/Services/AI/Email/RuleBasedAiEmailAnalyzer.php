<?php

namespace App\Services\AI\Email;

use App\Contracts\AI\AiEmailAnalyzerInterface;
use Illuminate\Support\Str;

class RuleBasedAiEmailAnalyzer implements AiEmailAnalyzerInterface
{
    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function analyze(array $input): array
    {
        $email = is_array($input['email'] ?? null) ? $input['email'] : $input;
        $subject = (string) ($email['subject'] ?? '');
        $body = (string) ($email['body_text'] ?? '');
        $text = trim($subject.' '.$body);
        $lower = Str::lower($text);

        $orderNumber = $this->firstMatch('/\bPO-\d{8}-\d+\b|\bPO-[A-Z0-9-]+\b/i', $text);
        $sku = $this->firstMatch('/\bSKU-[A-Z0-9-]+\b|\b[A-Z]{2,5}-\d{2,6}\b/', $text);
        $quantity = $this->firstQuantity($text);
        $date = $this->firstMatch('/\b\d{4}-\d{2}-\d{2}\b/', $text);
        $emailType = $this->emailType($lower);
        $confirmedItems = [];

        if ($sku !== null && $quantity !== null) {
            $confirmedItems[] = [
                'sku' => $sku,
                'manufacturer_sku' => null,
                'supplier_sku' => null,
                'confirmed_quantity' => $quantity,
                'unit' => 'pcs',
                'notes' => null,
                'confidence' => 0.75,
                'source_excerpt' => $this->excerpt($text, $sku),
            ];
        }

        return [
            'email_type' => $emailType,
            'supplier_order_number' => $orderNumber,
            'supplier_reference' => $this->firstMatch('/\b(?:CONF|REF)-[A-Z0-9-]+\b/i', $text),
            'confirmed_items' => $confirmedItems,
            'dates' => [
                'confirmation_date' => $date,
                'ready_date' => Str::contains($lower, 'ready') ? $date : null,
                'shipping_date' => Str::contains($lower, 'ship') ? $date : null,
                'pickup_date' => Str::contains($lower, 'pickup') ? $date : null,
                'delivery_date' => Str::contains($lower, 'deliver') ? $date : null,
                'expected_arrival_date' => Str::contains($lower, 'arrival') ? $date : null,
            ],
            'carrier_quote' => [
                'carrier_name' => null,
                'price' => $this->firstPrice($text),
                'currency' => $this->firstMatch('/\b(EUR|USD|GBP)\b/i', $text),
                'pickup_date' => Str::contains($lower, 'pickup') ? $date : null,
                'delivery_date' => Str::contains($lower, 'delivery') ? $date : null,
                'conditions' => null,
            ],
            'discrepancies' => [],
            'questions_to_supplier' => [],
            'confidence' => $emailType === 'unclear' ? 0.5 : 0.75,
            'requires_human_review' => true,
            'human_review_reason' => 'rule_based_analyzer_requires_human_review',
        ];
    }

    private function emailType(string $lower): string
    {
        if (Str::contains($lower, ['carrier', 'transport', 'freight', 'price', 'quote'])) {
            return 'transport_quote';
        }

        if (Str::contains($lower, ['confirm', 'confirmed', 'confirmation'])) {
            return 'supplier_confirmation';
        }

        if (Str::contains($lower, ['ready', 'delayed', 'delay', 'date'])) {
            return 'date_update';
        }

        return 'unclear';
    }

    private function firstMatch(string $pattern, string $text): ?string
    {
        preg_match($pattern, $text, $matches);

        return isset($matches[0]) ? strtoupper($matches[0]) : null;
    }

    private function firstQuantity(string $text): ?float
    {
        preg_match('/(?:quantity|qty)\s*[:#-]?\s*(\d+(?:[.,]\d+)?)|(\d+(?:[.,]\d+)?)\s*(?:pcs|units?)/i', $text, $matches);
        $value = $matches[1] ?? $matches[2] ?? null;

        return is_numeric(str_replace(',', '.', (string) $value)) ? (float) str_replace(',', '.', (string) $value) : null;
    }

    private function firstPrice(string $text): ?float
    {
        preg_match('/(?:EUR|USD|GBP)?\s*(\d+(?:[.,]\d{1,2})?)\s*(?:EUR|USD|GBP)?/i', $text, $matches);
        $value = $matches[1] ?? null;

        return is_numeric(str_replace(',', '.', (string) $value)) ? (float) str_replace(',', '.', (string) $value) : null;
    }

    private function excerpt(string $text, string $needle): string
    {
        $position = stripos($text, $needle);

        if ($position === false) {
            return Str::limit($text, 120);
        }

        return Str::of($text)->substr(max(0, $position - 40), 120)->toString();
    }
}
