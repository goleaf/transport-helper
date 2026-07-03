<?php

namespace App\Services\FormAutofill;

use App\Enums\FormFieldType;
use Carbon\Carbon;
use Throwable;

class FormFieldNormalizationService
{
    public function normalize(mixed $value, string|FormFieldType|null $fieldType): mixed
    {
        $type = $fieldType instanceof FormFieldType ? $fieldType->value : $fieldType;

        return match ($type) {
            'date' => $this->normalizeDate($value),
            'number', 'decimal' => $this->normalizeQuantity($value),
            'currency' => $this->normalizeCurrency($value),
            'sku' => $this->normalizeSku($value),
            'text' => $this->normalizeSupplierReference($value),
            default => is_string($value) ? trim($value) : $value,
        };
    }

    public function normalizeDate(mixed $value): ?string
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

    public function normalizeQuantity(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $cleaned = preg_replace('/[^0-9,.-]/', '', (string) $value);
        $cleaned = str_replace(',', '.', (string) $cleaned);

        return is_numeric($cleaned) ? (float) $cleaned : null;
    }

    public function normalizeCurrency(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return strtoupper(substr(trim((string) $value), 0, 3));
    }

    public function normalizeSku(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return strtoupper(trim((string) $value));
    }

    public function normalizeSupplierReference(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return trim((string) $value);
    }

    public function normalizeCarrierName(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return trim((string) $value);
    }
}
