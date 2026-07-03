<?php

namespace App\Services\Import;

use Carbon\CarbonImmutable;

class ImportValueNormalizer
{
    public function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    public function decimalOrNull(mixed $value): ?float
    {
        $value = $this->stringOrNull($value);

        if ($value === null) {
            return null;
        }

        $normalized = str_replace(' ', '', $value);

        if (str_contains($normalized, ',') && ! str_contains($normalized, '.')) {
            $normalized = str_replace(',', '.', $normalized);
        } else {
            $normalized = str_replace(',', '', $normalized);
        }

        if (! is_numeric($normalized)) {
            return null;
        }

        return (float) $normalized;
    }

    public function dateOrNull(mixed $value): ?string
    {
        $value = $this->stringOrNull($value);

        if ($value === null) {
            return null;
        }

        if (is_numeric($value) && (int) $value > 25000 && (int) $value < 80000) {
            return CarbonImmutable::create(1899, 12, 30)
                ->addDays((int) $value)
                ->toDateString();
        }

        foreach (['Y-m-d', 'd.m.Y', 'd/m/Y'] as $format) {
            try {
                $parsed = CarbonImmutable::createFromFormat($format, $value);
            } catch (\Throwable) {
                $parsed = null;
            }

            if ($parsed instanceof CarbonImmutable && $parsed->format($format) === $value) {
                return $parsed->toDateString();
            }
        }

        return null;
    }

    public function boolean(mixed $value): bool
    {
        $value = strtolower((string) ($this->stringOrNull($value) ?? ''));

        if ($value === '') {
            return false;
        }

        return in_array($value, ['1', 'yes', 'y', 'taip', 'true'], true);
    }

    public function sku(mixed $value): ?string
    {
        $value = $this->stringOrNull($value);

        return $value === null ? null : strtoupper($value);
    }

    public function integerOrNull(mixed $value): ?int
    {
        $decimal = $this->decimalOrNull($value);

        return $decimal === null ? null : (int) $decimal;
    }
}
