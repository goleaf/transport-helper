<?php

namespace App\Services\Forms;

use App\Enums\FormFieldType;
use Carbon\Carbon;
use Throwable;

class FormFieldNormalizationService
{
    /**
     * Compatibility helper for older callers that only need the normalized value.
     */
    public function normalize(mixed $value, string|FormFieldType|null $fieldType): mixed
    {
        return $this->normalizeByFieldType(
            $fieldType instanceof FormFieldType ? $fieldType->value : (string) $fieldType,
            $value,
        )['value'];
    }

    /**
     * @return array{success:bool,value:mixed,warning:?string,error:?string}
     */
    public function normalizeText(mixed $value): array
    {
        return $this->ok(is_string($value) ? trim($value) : $value);
    }

    /**
     * @return array{success:bool,value:mixed,warning:?string,error:?string}
     */
    public function normalizeDate(mixed $value): array
    {
        if ($value === null || $value === '') {
            return $this->ok(null);
        }

        $text = trim((string) $value);

        foreach (['Y-m-d', 'd.m.Y', 'd/m/Y', 'j M Y', 'j F Y'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $text);

                if ($date instanceof Carbon) {
                    return $this->ok($date->toDateString());
                }
            } catch (Throwable) {
                //
            }
        }

        try {
            return $this->ok(Carbon::parse($text)->toDateString());
        } catch (Throwable) {
            return $this->fail('invalid_date');
        }
    }

    /**
     * @return array{success:bool,value:mixed,warning:?string,error:?string}
     */
    public function normalizeDecimal(mixed $value): array
    {
        if ($value === null || $value === '') {
            return $this->ok(null);
        }

        if (is_numeric($value)) {
            return $this->ok((float) $value);
        }

        $text = preg_replace('/[^0-9,.\- ]/', '', (string) $value);
        $text = trim((string) $text);
        $text = str_replace(' ', '', $text);

        if (str_contains($text, ',') && ! str_contains($text, '.')) {
            $text = str_replace(',', '.', $text);
        } elseif (substr_count($text, ',') === 1 && substr_count($text, '.') === 1 && strpos($text, ',') > strpos($text, '.')) {
            $text = str_replace('.', '', $text);
            $text = str_replace(',', '.', $text);
        }

        return is_numeric($text) ? $this->ok((float) $text) : $this->fail('invalid_decimal');
    }

    /**
     * @return array{success:bool,value:mixed,warning:?string,error:?string}
     */
    public function normalizeCurrency(mixed $value): array
    {
        if ($value === null || $value === '') {
            return $this->ok(null);
        }

        $currency = strtoupper(trim((string) $value));
        $currency = substr($currency, 0, 3);
        $warning = in_array($currency, ['EUR', 'USD', 'GBP', 'PLN'], true) ? null : 'unknown_currency';

        return $this->ok($currency, $warning);
    }

    /**
     * @return array{success:bool,value:mixed,warning:?string,error:?string}
     */
    public function normalizeSku(mixed $value): array
    {
        if ($value === null || $value === '') {
            return $this->ok(null);
        }

        return $this->ok(strtoupper(trim((string) $value)));
    }

    /**
     * @return array{success:bool,value:mixed,warning:?string,error:?string}
     */
    public function normalizeEmail(mixed $value): array
    {
        if ($value === null || $value === '') {
            return $this->ok(null);
        }

        $email = strtolower(trim((string) $value));

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $this->ok($email) : $this->fail('invalid_email');
    }

    /**
     * @return array{success:bool,value:mixed,warning:?string,error:?string}
     */
    public function normalizeBoolean(mixed $value): array
    {
        if ($value === null || $value === '') {
            return $this->ok(null);
        }

        $text = strtolower(trim((string) $value));

        if (in_array($text, ['1', 'true', 'yes', 'y', 'taip'], true)) {
            return $this->ok(true);
        }

        if (in_array($text, ['0', 'false', 'no', 'n', 'ne'], true)) {
            return $this->ok(false);
        }

        return $this->fail('invalid_boolean');
    }

    /**
     * @return array{success:bool,value:mixed,warning:?string,error:?string}
     */
    public function normalizeByFieldType(string $fieldType, mixed $value): array
    {
        return match ($fieldType) {
            'date' => $this->normalizeDate($value),
            'number', 'decimal' => $this->normalizeDecimal($value),
            'currency' => $this->normalizeCurrency($value),
            'sku' => $this->normalizeSku($value),
            'email' => $this->normalizeEmail($value),
            'boolean' => $this->normalizeBoolean($value),
            default => $this->normalizeText($value),
        };
    }

    /**
     * @return array{success:bool,value:mixed,warning:?string,error:?string}
     */
    private function ok(mixed $value, ?string $warning = null): array
    {
        return [
            'success' => true,
            'value' => $value,
            'warning' => $warning,
            'error' => null,
        ];
    }

    /**
     * @return array{success:bool,value:mixed,warning:?string,error:?string}
     */
    private function fail(string $error): array
    {
        return [
            'success' => false,
            'value' => null,
            'warning' => null,
            'error' => $error,
        ];
    }
}
