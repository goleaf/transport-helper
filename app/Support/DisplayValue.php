<?php

namespace App\Support;

use BackedEnum;
use DateTimeInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DisplayValue
{
    public static function normalize(mixed $value): mixed
    {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i');
        }

        if ($value instanceof Collection) {
            return $value->all();
        }

        if ($value instanceof Arrayable) {
            return $value->toArray();
        }

        return $value;
    }

    public static function scalar(mixed $value, string $default = ''): string
    {
        $value = self::normalize($value);

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return $default;
    }

    public static function statusValue(mixed $status): string
    {
        $value = self::scalar($status, 'unknown');

        return $value === '' ? 'unknown' : $value;
    }

    public static function headline(string $value): string
    {
        return Str::of($value)->replace(['_', '-'], ' ')->headline()->toString();
    }

    public static function title(string $value): string
    {
        return Str::of($value)->replace(['_', '-'], ' ')->title()->toString();
    }

    public static function humanLabel(mixed $value): string
    {
        $value = self::scalar($value);

        $labels = [
            'json' => 'Structured data',
            'manual_json' => 'Manual structured data',
            'csv' => 'Spreadsheet',
            'excel_csv' => 'Excel spreadsheet',
            'pdf' => 'PDF document',
            'supplier_custom_template' => 'Supplier template',
            'internal_html' => 'Internal form',
        ];

        return $labels[$value] ?? self::headline($value);
    }

    public static function inlineList(mixed $items, string $empty = ''): string
    {
        $items = self::normalize($items);

        if (! is_array($items)) {
            return self::scalar($items, $empty);
        }

        $values = collect($items)
            ->map(fn (mixed $item): string => self::scalar($item))
            ->filter(fn (string $item): bool => $item !== '')
            ->values()
            ->all();

        return $values === [] ? $empty : implode(', ', $values);
    }

    public static function itemCount(mixed $items): int
    {
        $items = self::normalize($items);

        return is_countable($items) ? count($items) : 0;
    }

    public static function preview(mixed $value, int $limit = 500): string
    {
        return Str::limit(self::scalar($value), $limit);
    }
}
