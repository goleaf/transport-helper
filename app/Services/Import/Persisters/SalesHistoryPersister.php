<?php

namespace App\Services\Import\Persisters;

use App\Contracts\Import\ImportPersisterInterface;
use App\Models\SalesHistory;

class SalesHistoryPersister implements ImportPersisterInterface
{
    public function persist(array $row, array $context = []): array
    {
        $model = SalesHistory::query()->create([
            'company_id' => $row['company_id'],
            'product_id' => $row['product_id'],
            'sales_date' => $row['sales_date'],
            'quantity' => $row['quantity'],
            'channel' => $row['channel'] ?? null,
            'customer_id' => $row['customer_id'] ?? null,
            'is_promotion' => $row['is_promotion'] ?? false,
            'is_anomaly' => $row['is_anomaly'] ?? false,
            'anomaly_reason' => $row['anomaly_reason'] ?? null,
            'source_type' => $row['source_type'] ?? null,
            'source_reference' => $row['source_reference'] ?? null,
            'import_batch_id' => $row['import_batch_id'] ?? null,
        ]);

        return [
            'model_type' => SalesHistory::class,
            'model_id' => (int) $model->getKey(),
            'model' => $model,
        ];
    }
}
