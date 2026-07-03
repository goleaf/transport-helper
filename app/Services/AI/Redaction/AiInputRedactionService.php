<?php

namespace App\Services\AI\Redaction;

class AiInputRedactionService
{
    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    public function redact(array $input, array $rules = []): array
    {
        $redactions = [];
        $redacted = $this->redactValue($input, '', $rules, $redactions);

        return [
            'redacted_input' => is_array($redacted) ? $redacted : [],
            'redactions' => $redactions,
        ];
    }

    /**
     * @param  array<string, mixed>  $rules
     * @param  list<array<string, string>>  $redactions
     */
    private function redactValue(mixed $value, string $path, array $rules, array &$redactions): mixed
    {
        if (is_array($value)) {
            $redacted = [];

            foreach ($value as $key => $item) {
                $childPath = $path === '' ? (string) $key : $path.'.'.$key;
                $redacted[$key] = $this->redactValueByKey((string) $key, $item, $childPath, $rules, $redactions);
            }

            return $redacted;
        }

        if (! is_string($value)) {
            return $value;
        }

        return $this->redactString($value, $path, $rules, $redactions);
    }

    /**
     * @param  array<string, mixed>  $rules
     * @param  list<array<string, string>>  $redactions
     */
    private function redactValueByKey(string $key, mixed $value, string $path, array $rules, array &$redactions): mixed
    {
        if (preg_match('/(token|secret|password|api_key|client_secret|private_key)/i', $key) === 1) {
            $redactions[] = [
                'path' => $path,
                'type' => 'secret',
                'replacement' => '[SECRET]',
            ];

            return '[SECRET]';
        }

        return $this->redactValue($value, $path, $rules, $redactions);
    }

    /**
     * @param  array<string, mixed>  $rules
     * @param  list<array<string, string>>  $redactions
     */
    private function redactString(string $value, string $path, array $rules, array &$redactions): string
    {
        $patterns = [
            'email' => '/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i',
            'phone' => '/(?<!\d)(?:\+?\d[\d\s().-]{7,}\d)(?!\d)/',
        ];

        foreach ($patterns as $type => $pattern) {
            $replacement = $type === 'email' ? '[EMAIL]' : '[PHONE]';
            $value = preg_replace_callback($pattern, function () use ($path, $type, $replacement, &$redactions): string {
                $redactions[] = [
                    'path' => $path,
                    'type' => $type,
                    'replacement' => $replacement,
                ];

                return $replacement;
            }, $value) ?? $value;
        }

        foreach ((array) ($rules['customer_names'] ?? []) as $customerName) {
            if ($customerName !== '') {
                $value = $this->replaceLiteral($value, (string) $customerName, '[CUSTOMER]', $path, 'customer_name', $redactions);
            }
        }

        foreach ((array) ($rules['project_names'] ?? []) as $projectName) {
            if ($projectName !== '') {
                $value = $this->replaceLiteral($value, (string) $projectName, '[PROJECT]', $path, 'project_name', $redactions);
            }
        }

        if ((bool) ($rules['redact_prices'] ?? false)) {
            $value = preg_replace_callback('/(?:EUR|USD|GBP)?\s?\d+(?:[.,]\d{2})?\s?(?:EUR|USD|GBP)?/i', function () use ($path, &$redactions): string {
                $redactions[] = [
                    'path' => $path,
                    'type' => 'price',
                    'replacement' => '[PRICE]',
                ];

                return '[PRICE]';
            }, $value) ?? $value;
        }

        return $value;
    }

    /**
     * @param  list<array<string, string>>  $redactions
     */
    private function replaceLiteral(string $value, string $needle, string $replacement, string $path, string $type, array &$redactions): string
    {
        if (! str_contains($value, $needle)) {
            return $value;
        }

        $redactions[] = [
            'path' => $path,
            'type' => $type,
            'replacement' => $replacement,
        ];

        return str_replace($needle, $replacement, $value);
    }
}
