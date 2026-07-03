<?php

namespace App\Services\Supply\Analytics;

use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class AnalyticsFilterService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function normalize(array $filters): array
    {
        $period = (string) ($filters['report_period'] ?? 'last_30_days');
        [$dateFrom, $dateTo] = $this->dateRange($period, $filters);

        if ($dateFrom->greaterThan($dateTo)) {
            throw ValidationException::withMessages([
                'date_from' => 'The date from must be before or equal to date to.',
            ]);
        }

        $days = $dateFrom->diffInDays($dateTo) + 1;
        $warnings = [];

        if ($days < 7) {
            $warnings[] = 'Selected date range is short; trend metrics may be noisy.';
        }

        if ($days > 366) {
            $warnings[] = 'Selected date range is large; report may mix old operating patterns with current performance.';
        }

        return [
            'company_id' => $this->nullableInt($filters['company_id'] ?? null),
            'supplier_id' => $this->nullableInt($filters['supplier_id'] ?? null),
            'carrier_id' => $this->nullableInt($filters['carrier_id'] ?? null),
            'product_id' => $this->nullableInt($filters['product_id'] ?? null),
            'category' => $this->nullableString($filters['category'] ?? null),
            'date_from' => $dateFrom->toDateString(),
            'date_to' => $dateTo->toDateString(),
            'status' => $this->nullableString($filters['status'] ?? null),
            'report_period' => $period,
            'include_archived' => filter_var($filters['include_archived'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'group_by' => $this->nullableString($filters['group_by'] ?? null),
            'compare_to_previous_period' => filter_var($filters['compare_to_previous_period'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'warnings' => $warnings,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function dateRange(string $period, array $filters): array
    {
        $today = CarbonImmutable::today();

        if ($period === 'custom' || isset($filters['date_from']) || isset($filters['date_to'])) {
            return [
                $this->parseDate($filters['date_from'] ?? $today->subDays(30)->toDateString(), 'date_from'),
                $this->parseDate($filters['date_to'] ?? $today->toDateString(), 'date_to'),
            ];
        }

        return match ($period) {
            'last_7_days' => [$today->subDays(7), $today],
            'this_month' => [$today->startOfMonth(), $today],
            'last_month' => [$today->subMonthNoOverflow()->startOfMonth(), $today->subMonthNoOverflow()->endOfMonth()],
            'this_quarter' => [$today->startOfQuarter(), $today],
            'this_year' => [$today->startOfYear(), $today],
            default => [$today->subDays(30), $today],
        };
    }

    private function parseDate(mixed $value, string $field): CarbonImmutable
    {
        try {
            return CarbonImmutable::parse((string) $value)->startOfDay();
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                $field => 'The '.$field.' is not a valid date.',
            ]);
        }
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }
}
