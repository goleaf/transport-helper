<?php

namespace App\Services\Supply;

class TrendCalculator
{
    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function calculate(array $input): array
    {
        $currentYearSales = $this->number($input, 'current_year_sales_for_trend');
        $lastYearSales = $this->number($input, 'last_year_sales_for_trend');
        $fallbackStrategy = $input['fallback_strategy'] ?? null;

        if ($lastYearSales === 0.0) {
            $warnings = ['insufficient_last_year_sales'];
            $trend = null;
            $appliedFallback = null;

            if ($fallbackStrategy === 'neutral_trend') {
                $trend = 1.0;
                $appliedFallback = 'neutral_trend';
                $warnings[] = 'fallback_neutral_trend_applied';
            } elseif (is_numeric($fallbackStrategy)) {
                $trend = (float) $fallbackStrategy;
                $appliedFallback = 'numeric_fallback_trend';
                $warnings[] = 'fallback_numeric_trend_applied';
            }

            return [
                'status' => 'needs_review',
                'trend' => $trend,
                'warnings' => $warnings,
                'requires_human_review' => true,
                'applied_fallback' => $appliedFallback,
            ];
        }

        return [
            'status' => 'draft',
            'trend' => $currentYearSales / $lastYearSales,
            'warnings' => [],
            'requires_human_review' => false,
            'applied_fallback' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function number(array $input, string $key): float
    {
        return isset($input[$key]) && is_numeric($input[$key]) ? (float) $input[$key] : 0.0;
    }
}
