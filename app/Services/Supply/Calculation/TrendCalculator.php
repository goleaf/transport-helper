<?php

namespace App\Services\Supply\Calculation;

class TrendCalculator
{
    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function calculate(array $input): array
    {
        $warnings = [];
        $errors = [];

        $currentSales = $this->number($input['current_year_sales_for_trend'] ?? null);
        $lastSales = $this->number($input['last_year_sales_for_trend'] ?? null);

        if ($currentSales === null) {
            $warnings[] = 'missing_current_year_sales_for_trend';
        }

        if ($lastSales === null) {
            $warnings[] = 'missing_last_year_sales_for_trend';
        }

        if (($currentSales !== null && $currentSales < 0) || ($lastSales !== null && $lastSales < 0)) {
            $warnings[] = 'negative_sales_for_trend';
        }

        if ($warnings !== []) {
            return $this->reviewResult(null, $warnings, $errors, null, null);
        }

        if ($lastSales === 0.0) {
            if (($input['fallback_strategy'] ?? null) === 'manual_trend' && $this->number($input['manual_trend'] ?? null) !== null && (float) $input['manual_trend'] > 0) {
                $manualTrend = (float) $input['manual_trend'];

                return [
                    'status' => 'needs_review',
                    'trend' => $manualTrend,
                    'requires_human_review' => true,
                    'warnings' => ['manual_trend_used'],
                    'errors' => [],
                    'explanation' => [
                        'formula' => 'manual_trend',
                        'calculation' => (string) $manualTrend,
                        'value' => $manualTrend,
                    ],
                ];
            }

            return $this->reviewResult(null, ['insufficient_last_year_sales'], $errors, $currentSales, $lastSales);
        }

        $trend = $currentSales / $lastSales;

        return [
            'status' => 'ok',
            'trend' => $trend,
            'requires_human_review' => false,
            'warnings' => [],
            'errors' => [],
            'explanation' => [
                'formula' => 'current_year_sales_for_trend / last_year_sales_for_trend',
                'calculation' => $this->formatNumber($currentSales).' / '.$this->formatNumber($lastSales).' = '.$this->formatNumber($trend),
                'value' => $trend,
            ],
        ];
    }

    private function number(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    /**
     * @param  list<string>  $warnings
     * @param  list<string>  $errors
     * @return array<string, mixed>
     */
    private function reviewResult(?float $trend, array $warnings, array $errors, ?float $currentSales, ?float $lastSales): array
    {
        return [
            'status' => 'needs_review',
            'trend' => $trend,
            'requires_human_review' => true,
            'warnings' => $warnings,
            'errors' => $errors,
            'explanation' => [
                'formula' => 'current_year_sales_for_trend / last_year_sales_for_trend',
                'calculation' => $currentSales === null || $lastSales === null
                    ? null
                    : $this->formatNumber($currentSales).' / '.$this->formatNumber($lastSales),
                'value' => $trend,
            ],
        ];
    }

    private function formatNumber(float $value): string
    {
        return rtrim(rtrim(number_format($value, 6, '.', ''), '0'), '.');
    }
}
