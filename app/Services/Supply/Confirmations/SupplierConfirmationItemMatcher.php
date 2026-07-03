<?php

namespace App\Services\Supply\Confirmations;

use App\Models\SupplierOrder;
use App\Models\SupplierOrderItem;
use Illuminate\Support\Collection;

class SupplierConfirmationItemMatcher
{
    /**
     * @param  array<string, mixed>  $confirmedItem
     * @return array<string, mixed>
     */
    public function match(SupplierOrder $order, array $confirmedItem): array
    {
        $order->loadMissing('items.product.supplierProductRules');

        if (isset($confirmedItem['product_id']) && is_numeric($confirmedItem['product_id'])) {
            $matches = $order->items
                ->filter(fn (SupplierOrderItem $item): bool => (int) $item->product_id === (int) $confirmedItem['product_id'])
                ->values();

            return $this->resultFromMatches($matches, 'product_id');
        }

        foreach ([
            'sku' => 'sku',
            'manufacturer_sku' => 'manufacturer_sku',
            'supplier_sku' => 'supplier_sku',
        ] as $inputKey => $matchBy) {
            $value = $this->skuKey($confirmedItem[$inputKey] ?? null);

            if ($value === null) {
                continue;
            }

            $matches = $order->items
                ->filter(function (SupplierOrderItem $item) use ($value, $matchBy, $order): bool {
                    $product = $item->product;

                    if ($matchBy === 'sku') {
                        return $this->skuKey($product?->sku) === $value;
                    }

                    if ($matchBy === 'manufacturer_sku') {
                        return $this->skuKey($product?->manufacturer_sku) === $value;
                    }

                    return $product?->supplierProductRules
                        ->filter(fn ($rule): bool => (int) $rule->supplier_id === (int) $order->supplier_id)
                        ->contains(fn ($rule): bool => $this->skuKey($rule->supplier_sku) === $value) ?? false;
                })
                ->values();

            if ($matches->isNotEmpty()) {
                return $this->resultFromMatches($matches, $matchBy);
            }
        }

        return [
            'matched' => false,
            'ambiguous' => false,
            'supplier_order_item' => null,
            'product' => null,
            'matched_by' => null,
            'confidence' => 0.0,
            'warnings' => ['unknown_sku'],
            'errors' => [],
            'candidates' => [],
        ];
    }

    /**
     * @param  Collection<int, SupplierOrderItem>  $matches
     * @return array<string, mixed>
     */
    private function resultFromMatches(Collection $matches, string $matchedBy): array
    {
        if ($matches->count() === 1) {
            $item = $matches->first();

            return [
                'matched' => true,
                'ambiguous' => false,
                'supplier_order_item' => $item,
                'product' => $item?->product,
                'matched_by' => $matchedBy,
                'confidence' => 1.0,
                'warnings' => [],
                'errors' => [],
                'candidates' => [],
            ];
        }

        return [
            'matched' => false,
            'ambiguous' => true,
            'supplier_order_item' => null,
            'product' => null,
            'matched_by' => null,
            'confidence' => 0.0,
            'warnings' => ['ambiguous_sku'],
            'errors' => [],
            'candidates' => $matches->map(fn (SupplierOrderItem $item): array => [
                'supplier_order_item_id' => $item->getKey(),
                'product_id' => $item->product_id,
                'sku' => $item->product?->sku,
            ])->values()->all(),
        ];
    }

    private function skuKey(mixed $value): ?string
    {
        $text = trim((string) $value);

        return $text === '' ? null : mb_strtoupper($text);
    }
}
