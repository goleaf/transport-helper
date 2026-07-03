<?php

namespace App\Services\Supply\Forecasting;

use App\Models\CalculationScenario;
use App\Models\OrderProposal;
use App\Models\OrderProposalItem;

class ScenarioComparisonService
{
    /**
     * @return array<string, mixed>
     */
    public function compare(CalculationScenario $scenarioA, CalculationScenario $scenarioB): array
    {
        $scenarioA->loadMissing(['items.product:id,sku,name']);
        $scenarioB->loadMissing(['items.product:id,sku,name']);

        return $this->compareRows(
            $scenarioA->items->mapWithKeys(fn ($item): array => [(int) $item->product_id => $this->scenarioRow($item)])->all(),
            $scenarioB->items->mapWithKeys(fn ($item): array => [(int) $item->product_id => $this->scenarioRow($item)])->all(),
            'Scenario A',
            'Scenario B',
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function compareWithProposal(CalculationScenario $scenario, OrderProposal $proposal): array
    {
        $scenario->loadMissing(['items.product:id,sku,name']);
        $proposal->loadMissing(['items.product:id,sku,name']);

        return $this->compareRows(
            $proposal->items->mapWithKeys(fn (OrderProposalItem $item): array => [
                (int) $item->product_id => [
                    'product_id' => (int) $item->product_id,
                    'sku' => $item->product?->sku,
                    'name' => $item->product?->name,
                    'quantity' => (float) $item->recommended_quantity,
                    'raw_need' => (float) $item->raw_need,
                    'trend' => (float) $item->trend,
                    'warnings' => [],
                ],
            ])->all(),
            $scenario->items->mapWithKeys(fn ($item): array => [(int) $item->product_id => $this->scenarioRow($item)])->all(),
            'Proposal',
            'Scenario',
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $aRows
     * @param  array<int, array<string, mixed>>  $bRows
     * @return array<string, mixed>
     */
    private function compareRows(array $aRows, array $bRows, string $aLabel, string $bLabel): array
    {
        $productIds = collect(array_keys($aRows))
            ->merge(array_keys($bRows))
            ->unique()
            ->sort()
            ->values();

        $rows = $productIds->map(function (int $productId) use ($aRows, $bRows, $aLabel, $bLabel): array {
            $a = $aRows[$productId] ?? null;
            $b = $bRows[$productId] ?? null;
            $aQuantity = $a['quantity'] ?? 0.0;
            $bQuantity = $b['quantity'] ?? 0.0;
            $difference = round((float) $bQuantity - (float) $aQuantity, 4);
            $percent = (float) $aQuantity !== 0.0 ? round($difference / (float) $aQuantity, 6) : null;

            return [
                'product_id' => $productId,
                'sku' => $b['sku'] ?? $a['sku'] ?? null,
                'product_name' => $b['name'] ?? $a['name'] ?? null,
                'a_label' => $aLabel,
                'b_label' => $bLabel,
                'a_quantity' => $aQuantity,
                'b_quantity' => $bQuantity,
                'difference' => $difference,
                'difference_percent' => $percent,
                'a_raw_need' => $a['raw_need'] ?? null,
                'b_raw_need' => $b['raw_need'] ?? null,
                'a_trend' => $a['trend'] ?? null,
                'b_trend' => $b['trend'] ?? null,
                'reason_summary' => $this->reasonSummary($a, $b),
            ];
        })->values()->all();

        return [
            'summary' => [
                'items_compared' => count($rows),
                'increased_count' => collect($rows)->filter(fn (array $row): bool => (float) $row['difference'] > 0)->count(),
                'decreased_count' => collect($rows)->filter(fn (array $row): bool => (float) $row['difference'] < 0)->count(),
                'unchanged_count' => collect($rows)->filter(fn (array $row): bool => (float) $row['difference'] === 0.0)->count(),
                'total_quantity_difference' => round(collect($rows)->sum(fn (array $row): float => (float) $row['difference']), 4),
            ],
            'rows' => $rows,
            'warnings' => collect($rows)
                ->filter(fn (array $row): bool => $row['reason_summary'] === 'Product missing from one side.')
                ->isNotEmpty() ? ['products_missing_from_one_side'] : [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function scenarioRow(mixed $item): array
    {
        return [
            'product_id' => (int) $item->product_id,
            'sku' => $item->product?->sku,
            'name' => $item->product?->name,
            'quantity' => (float) $item->simulated_recommended_quantity,
            'raw_need' => (float) $item->simulated_raw_need,
            'trend' => $item->trend_used !== null ? (float) $item->trend_used : null,
            'seasonality_factor' => $item->seasonality_factor !== null ? (float) $item->seasonality_factor : null,
            'warnings' => $item->warnings_json ?? [],
        ];
    }

    /**
     * @param  array<string, mixed>|null  $a
     * @param  array<string, mixed>|null  $b
     */
    private function reasonSummary(?array $a, ?array $b): string
    {
        if ($a === null || $b === null) {
            return 'Product missing from one side.';
        }

        if (($a['seasonality_factor'] ?? 1.0) !== ($b['seasonality_factor'] ?? 1.0)) {
            return 'Seasonality factor changed.';
        }

        if (($a['trend'] ?? null) !== ($b['trend'] ?? null)) {
            return 'Trend input changed.';
        }

        if (($b['warnings'] ?? []) !== []) {
            return 'Scenario warnings changed.';
        }

        return 'Recommended quantity changed by deterministic inputs.';
    }
}
