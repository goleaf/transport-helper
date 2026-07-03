<?php

namespace App\Services\Import\Persisters;

use App\Contracts\Import\ImportPersisterInterface;
use App\Models\Product;
use App\Models\SupplierProductRule;

class ProductRulePersister implements ImportPersisterInterface
{
    public function persist(array $row, array $context = []): array
    {
        $model = SupplierProductRule::query()->updateOrCreate(
            [
                'supplier_id' => $row['supplier_id'],
                'product_id' => $row['product_id'],
            ],
            [
                'supplier_sku' => $row['supplier_sku'] ?? null,
                'moq' => $row['moq'] ?? null,
                'pack_multiple' => $row['pack_multiple'] ?? null,
                'pallet_quantity' => $row['pallet_quantity'] ?? null,
                'min_transport_quantity' => $row['min_transport_quantity'] ?? null,
                'lead_time_days' => $row['lead_time_days'] ?? null,
                'safety_days' => $row['safety_days'] ?? null,
                'safety_rule_type' => $row['safety_rule_type'] ?? null,
                'transport_rule_type' => $row['transport_rule_type'] ?? null,
                'order_enabled' => $row['order_enabled'] ?? true,
            ],
        );

        if (! empty($row['manufacturer_sku'])) {
            $product = Product::query()->select(['id', 'manufacturer_sku'])->find($row['product_id']);

            if ($product instanceof Product && ($product->manufacturer_sku === null || ($context['allow_product_update'] ?? false) === true)) {
                $product->update(['manufacturer_sku' => $row['manufacturer_sku']]);
            }
        }

        return [
            'model_type' => SupplierProductRule::class,
            'model_id' => (int) $model->getKey(),
            'model' => $model,
        ];
    }
}
