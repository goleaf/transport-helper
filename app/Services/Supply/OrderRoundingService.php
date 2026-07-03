<?php

namespace App\Services\Supply;

class OrderRoundingService
{
    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function round(array $input): array
    {
        $rawNeed = $this->number($input, 'raw_need');
        $strategicMinimumOrderEnabled = (bool) ($input['strategic_minimum_order_enabled'] ?? false);
        $quantity = max(0.0, $rawNeed);
        $warnings = [];
        $appliedRules = [];
        $roundingSteps = [
            [
                'rule' => 'raw_need_minimum',
                'input' => $rawNeed,
                'output' => $quantity,
            ],
        ];

        if ($rawNeed < 0.0 && ! $strategicMinimumOrderEnabled) {
            return [
                'recommended_quantity' => 0.0,
                'applied_rules' => [
                    'raw_need_minimum' => [
                        'from' => $rawNeed,
                        'to' => 0.0,
                    ],
                ],
                'warnings' => [],
                'rounding_steps' => $roundingSteps,
            ];
        }

        $moq = $this->nullableNumber($input, 'moq');
        if (($rawNeed > 0.0 || $strategicMinimumOrderEnabled) && $moq !== null && $moq > 0.0 && $quantity < $moq) {
            $before = $quantity;
            $quantity = $moq;
            $appliedRules['moq'] = [
                'value' => $moq,
                'from' => $before,
                'to' => $quantity,
            ];
            $roundingSteps[] = [
                'rule' => 'moq',
                'input' => $before,
                'output' => $quantity,
            ];
        }

        $packMultiple = $this->nullableNumber($input, 'pack_multiple');
        if ($quantity > 0.0 && $packMultiple !== null && $packMultiple > 0.0) {
            $before = $quantity;
            $quantity = $this->roundUpToMultiple($quantity, $packMultiple);

            if ($quantity !== $before) {
                $appliedRules['pack_multiple'] = [
                    'value' => $packMultiple,
                    'from' => $before,
                    'to' => $quantity,
                ];
            }

            $roundingSteps[] = [
                'rule' => 'pack_multiple',
                'input' => $before,
                'output' => $quantity,
            ];
        }

        $palletQuantity = $this->nullableNumber($input, 'pallet_quantity');
        $palletStrategy = $this->strategy($input, 'pallet_quantity', 'show_only');
        if ($quantity > 0.0 && $palletQuantity !== null && $palletQuantity > 0.0) {
            $before = $quantity;
            $rounded = $this->roundUpToMultiple($quantity, $palletQuantity);

            if ($palletStrategy === 'enforce_full_pallet') {
                $quantity = $rounded;
                $appliedRules['pallet_quantity'] = [
                    'strategy' => $palletStrategy,
                    'value' => $palletQuantity,
                    'from' => $before,
                    'to' => $quantity,
                ];
            } elseif ($rounded !== $quantity) {
                $warnings[] = 'pallet_quantity_not_full_pallet';
                $appliedRules['pallet_quantity'] = [
                    'strategy' => $palletStrategy,
                    'value' => $palletQuantity,
                    'would_round_to' => $rounded,
                ];
            }

            $roundingSteps[] = [
                'rule' => 'pallet_quantity',
                'strategy' => $palletStrategy,
                'input' => $before,
                'output' => $quantity,
                'would_round_to' => $rounded,
            ];
        }

        $minTransportQuantity = $this->nullableNumber($input, 'min_transport_quantity');
        $minTransportStrategy = $this->strategy($input, 'min_transport_quantity', 'show_only');
        if (($quantity > 0.0 || $strategicMinimumOrderEnabled) && $minTransportQuantity !== null && $minTransportQuantity > 0.0 && $quantity < $minTransportQuantity) {
            $before = $quantity;

            if ($minTransportStrategy === 'enforce_min_transport') {
                $quantity = $minTransportQuantity;
                $appliedRules['min_transport_quantity'] = [
                    'strategy' => $minTransportStrategy,
                    'value' => $minTransportQuantity,
                    'from' => $before,
                    'to' => $quantity,
                ];
            } else {
                $warnings[] = 'below_min_transport_quantity';
                $appliedRules['min_transport_quantity'] = [
                    'strategy' => $minTransportStrategy,
                    'value' => $minTransportQuantity,
                    'would_round_to' => $minTransportQuantity,
                ];
            }

            $roundingSteps[] = [
                'rule' => 'min_transport_quantity',
                'strategy' => $minTransportStrategy,
                'input' => $before,
                'output' => $quantity,
                'would_round_to' => $minTransportQuantity,
            ];
        }

        return [
            'recommended_quantity' => $quantity,
            'applied_rules' => array_merge([
                'raw_need_minimum' => [
                    'from' => $rawNeed,
                    'to' => max(0.0, $rawNeed),
                ],
            ], $appliedRules),
            'warnings' => $warnings,
            'rounding_steps' => $roundingSteps,
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function strategy(array $input, string $key, string $default): string
    {
        $roundingStrategy = $input['rounding_strategy'] ?? [];

        if (is_array($roundingStrategy) && isset($roundingStrategy[$key]) && is_string($roundingStrategy[$key])) {
            return $roundingStrategy[$key];
        }

        $strategyKey = $key.'_strategy';

        if (isset($input[$strategyKey]) && is_string($input[$strategyKey])) {
            return $input[$strategyKey];
        }

        if (is_string($roundingStrategy)) {
            return $roundingStrategy;
        }

        return $default;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function number(array $input, string $key): float
    {
        return isset($input[$key]) && is_numeric($input[$key]) ? (float) $input[$key] : 0.0;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function nullableNumber(array $input, string $key): ?float
    {
        return isset($input[$key]) && is_numeric($input[$key]) ? (float) $input[$key] : null;
    }

    private function roundUpToMultiple(float $quantity, float $multiple): float
    {
        return ceil($quantity / $multiple) * $multiple;
    }
}
