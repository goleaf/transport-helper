<?php

namespace App\Services\Supply\Procurement;

class ProcurementCurrencyService
{
    public function normalizeCurrency(?string $currency, ?string $fallback = 'EUR'): string
    {
        $value = strtoupper(trim((string) ($currency ?: $fallback ?: 'EUR')));

        return $value !== '' ? $value : 'EUR';
    }

    /**
     * @param  array<string, float|int|string>  $rates
     * @return array<string, mixed>
     */
    public function convert(float $amount, string $from, string $to, array $rates = []): array
    {
        $fromCurrency = $this->normalizeCurrency($from);
        $toCurrency = $this->normalizeCurrency($to);

        if ($fromCurrency === $toCurrency) {
            return [
                'amount' => $amount,
                'currency' => $fromCurrency,
                'converted_amount' => round($amount, 4),
                'converted_currency' => $toCurrency,
                'warnings' => [],
            ];
        }

        $configuredRates = array_merge(config('supply.procurement.manual_currency_rates', []), $rates);
        $fromRate = (float) ($configuredRates[$fromCurrency] ?? 0);
        $toRate = (float) ($configuredRates[$toCurrency] ?? 0);

        if ($fromRate <= 0 || $toRate <= 0) {
            return [
                'amount' => $amount,
                'currency' => $fromCurrency,
                'converted_amount' => null,
                'converted_currency' => $toCurrency,
                'warnings' => ['currency_conversion_missing'],
            ];
        }

        $baseAmount = $amount / $fromRate;
        $converted = $baseAmount * $toRate;

        return [
            'amount' => $amount,
            'currency' => $fromCurrency,
            'converted_amount' => round($converted, 4),
            'converted_currency' => $toCurrency,
            'warnings' => [],
        ];
    }
}
