<?php

namespace App\Services\Import\Persisters;

use App\Contracts\Import\ImportPersisterInterface;
use App\Models\StockSnapshot;

class StockSnapshotPersister implements ImportPersisterInterface
{
    public function persist(array $row, array $context = []): array
    {
        $model = StockSnapshot::query()->create([
            'company_id' => $row['company_id'],
            'product_id' => $row['product_id'],
            'snapshot_date' => $row['snapshot_date'],
            'free_stock' => $row['free_stock'],
            'total_stock' => $row['total_stock'] ?? null,
            'reserved_quantity' => $row['reserved_quantity'] ?? null,
            'damaged_quantity' => $row['damaged_quantity'] ?? null,
            'inactive_quantity' => $row['inactive_quantity'] ?? null,
            'in_transit_quantity' => $row['in_transit_quantity'] ?? null,
            'source_type' => $row['source_type'] ?? null,
            'source_reference' => $row['source_reference'] ?? null,
            'import_batch_id' => $row['import_batch_id'] ?? null,
        ]);

        return [
            'model_type' => StockSnapshot::class,
            'model_id' => (int) $model->getKey(),
            'model' => $model,
        ];
    }
}
