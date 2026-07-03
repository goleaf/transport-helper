<?php

namespace App\Services\Supply\Calculation;

class OrderRoundingService
{
    /**
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    public function round(int|float|string|null $rawNeed, array $rules = []): array
    {
        $normalizedRawNeed = $this->number($rawNeed);

        if ($normalizedRawNeed === null) {
            return [
                'status' => 'needs_review',
                'quantity' => 0.0,
                'applied_rules' => [],
                'warnings' => ['invalid_raw_need'],
                'rounding_steps' => [],
            ];
        }

        $strategicMinimum = (bool) ($rules['strategic_minimum_order_enabled'] ?? false);
        $warnings = [];
        $appliedRules = [];
        $roundingSteps = [];

        if ($normalizedRawNeed < 0) {
            $warnings[] = 'raw_need_below_zero';
        }

        $quantity = $normalizedRawNeed < 0 && ! $strategicMinimum
            ? 0.0
            : (float) ceil(max(0.0, $normalizedRawNeed));

        $roundingSteps[] = [
            'name' => 'base_quantity',
            'calculation' => $normalizedRawNeed < 0 && ! $strategicMinimum
                ? 'raw_need below zero becomes 0'
                : 'ceil('.$this->formatNumber(max(0.0, $normalizedRawNeed)).') = '.$this->formatNumber($quantity),
            'value' => $quantity,
        ];

        $moq = $this->number($rules['moq'] ?? null);

        if (($quantity > 0 || $strategicMinimum) && $moq !== null && $moq > 0 && $quantity < $moq) {
            $before = $quantity;
            $quantity = $moq;
            $appliedRules[] = [
                'rule' => 'moq',
                'input_quantity' => $before,
                'rule_value' => $moq,
                'output_quantity' => $quantity,
            ];
            $roundingSteps[] = [
                'name' => 'moq',
                'calculation' => 'max('.$this->formatNumber($before).', '.$this->formatNumber($moq).') = '.$this->formatNumber($quantity),
                'value' => $quantity,
            ];
        }

        $packMultiple = $this->number($rules['pack_multiple'] ?? null);

        if ($quantity > 0 && $packMultiple !== null && $packMultiple > 0) {
            $before = $quantity;
            $quantity = $this->roundUpToMultiple($quantity, $packMultiple);
            $appliedRules[] = [
                'rule' => 'pack_multiple',
                'input_quantity' => $before,
                'rule_value' => $packMultiple,
                'output_quantity' => $quantity,
            ];
            $roundingSteps[] = [
                'name' => 'pack_multiple',
                'calculation' => 'ceil('.$this->formatNumber($before).' / '.$this->formatNumber($packMultiple).') * '.$this->formatNumber($packMultiple).' = '.$this->formatNumber($quantity),
                'value' => $quantity,
            ];
        }

        $palletQuantity = $this->number($rules['pallet_quantity'] ?? null);
        $palletStrategy = $this->strategy($rules, 'pallet_strategy', 'pallet', 'show_only');

        if ($quantity > 0 && $palletQuantity !== null && $palletQuantity > 0) {
            $before = $quantity;
            $rounded = $this->roundUpToMultiple($quantity, $palletQuantity);

            if ($palletStrategy === 'enforce_full_pallet') {
                $quantity = $rounded;
                $appliedRules[] = [
                    'rule' => 'pallet_quantity',
                    'strategy' => $palletStrategy,
                    'input_quantity' => $before,
                    'rule_value' => $palletQuantity,
                    'output_quantity' => $quantity,
                ];
            } elseif ($rounded !== $quantity) {
                $warnings[] = 'pallet_quantity_show_only';
                $appliedRules[] = [
                    'rule' => 'pallet_quantity',
                    'strategy' => 'show_only',
                    'input_quantity' => $before,
                    'rule_value' => $palletQuantity,
                    'suggested_quantity' => $rounded,
                    'output_quantity' => $quantity,
                ];
            }

            $roundingSteps[] = [
                'name' => 'pallet_quantity',
                'strategy' => $palletStrategy,
                'calculation' => 'ceil('.$this->formatNumber($before).' / '.$this->formatNumber($palletQuantity).') * '.$this->formatNumber($palletQuantity).' = '.$this->formatNumber($rounded),
                'value' => $quantity,
                'suggested_quantity' => $rounded,
            ];
        }

        $minTransportQuantity = $this->number($rules['min_transport_quantity'] ?? null);
        $transportStrategy = $this->strategy($rules, 'transport_strategy', 'transport', 'show_only');

        if ($quantity > 0 && $minTransportQuantity !== null && $minTransportQuantity > 0 && $quantity < $minTransportQuantity) {
            $before = $quantity;

            if ($transportStrategy === 'enforce_min_transport') {
                $quantity = $minTransportQuantity;
                $appliedRules[] = [
                    'rule' => 'min_transport_quantity',
                    'strategy' => $transportStrategy,
                    'input_quantity' => $before,
                    'rule_value' => $minTransportQuantity,
                    'output_quantity' => $quantity,
                ];

                if ($packMultiple !== null && $packMultiple > 0) {
                    $afterTransport = $quantity;
                    $quantity = $this->roundUpToMultiple($quantity, $packMultiple);

                    if ($quantity !== $afterTransport) {
                        $appliedRules[] = [
                            'rule' => 'pack_multiple_after_transport',
                            'input_quantity' => $afterTransport,
                            'rule_value' => $packMultiple,
                            'output_quantity' => $quantity,
                        ];
                    }
                }
            } else {
                $warnings[] = 'min_transport_quantity_show_only';
                $appliedRules[] = [
                    'rule' => 'min_transport_quantity',
                    'strategy' => 'show_only',
                    'input_quantity' => $before,
                    'rule_value' => $minTransportQuantity,
                    'suggested_quantity' => $minTransportQuantity,
                    'output_quantity' => $quantity,
                ];
            }

            $roundingSteps[] = [
                'name' => 'min_transport_quantity',
                'strategy' => $transportStrategy,
                'calculation' => 'max('.$this->formatNumber($before).', '.$this->formatNumber($minTransportQuantity).') = '.$this->formatNumber(max($before, $minTransportQuantity)),
                'value' => $quantity,
            ];
        }

        return [
            'status' => 'ok',
            'quantity' => $quantity,
            'applied_rules' => $appliedRules,
            'warnings' => array_values(array_unique($warnings)),
            'rounding_steps' => $roundingSteps,
        ];
    }

    private function number(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    /**
     * @param  array<string, mixed>  $rules
     */
    private function strategy(array $rules, string $directKey, string $nestedKey, string $default): string
    {
        if (isset($rules[$directKey]) && is_string($rules[$directKey])) {
            return $rules[$directKey];
        }

        $roundingStrategy = $rules['rounding_strategy'] ?? [];

        if (is_array($roundingStrategy) && isset($roundingStrategy[$nestedKey]) && is_string($roundingStrategy[$nestedKey])) {
            return $roundingStrategy[$nestedKey];
        }

        return $default;
    }

    private function roundUpToMultiple(float $quantity, float $multiple): float
    {
        return (float) (ceil($quantity / $multiple) * $multiple);
    }

    private function formatNumber(float $value): string
    {
        return rtrim(rtrim(number_format($value, 6, '.', ''), '0'), '.');
    }
}
