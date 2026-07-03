<?php

namespace App\Actions;

use InvalidArgumentException;

class CalculateSupplyOrderQuantitiesAction
{
    public const int DEFAULT_RESERVE_PERCENT = 4;

    /**
     * @return array{
     *     t0_requested_quantity: int,
     *     t1_available_quantity: int,
     *     t2_required_quantity: int,
     *     t3_manufacturer_quantity: int,
     *     reserve_percent: int
     * }
     */
    public function handle(
        int $requestedQuantity,
        int $availableQuantity,
        int $incomingQuantity = 0,
        int $reservedQuantity = 0,
        int $reservePercent = self::DEFAULT_RESERVE_PERCENT,
    ): array {
        foreach ([$requestedQuantity, $availableQuantity, $incomingQuantity, $reservedQuantity, $reservePercent] as $quantity) {
            if ($quantity < 0) {
                throw new InvalidArgumentException('Supply calculation quantities cannot be negative.');
            }
        }

        $usableStock = max(0, $availableQuantity + $incomingQuantity - $reservedQuantity);
        $requiredQuantity = max(0, $requestedQuantity - $usableStock);
        $manufacturerQuantity = $requiredQuantity === 0
            ? 0
            : (int) ceil($requiredQuantity * (100 + $reservePercent) / 100);

        return [
            't0_requested_quantity' => $requestedQuantity,
            't1_available_quantity' => $usableStock,
            't2_required_quantity' => $requiredQuantity,
            't3_manufacturer_quantity' => $manufacturerQuantity,
            'reserve_percent' => $reservePercent,
        ];
    }
}
