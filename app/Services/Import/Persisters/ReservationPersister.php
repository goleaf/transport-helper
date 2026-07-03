<?php

namespace App\Services\Import\Persisters;

use App\Contracts\Import\ImportPersisterInterface;
use App\Models\Reservation;

class ReservationPersister implements ImportPersisterInterface
{
    public function persist(array $row, array $context = []): array
    {
        $model = Reservation::query()->create([
            'company_id' => $row['company_id'],
            'product_id' => $row['product_id'],
            'quantity' => $row['quantity'],
            'project_name' => $row['project_name'] ?? null,
            'customer_name' => $row['customer_name'] ?? null,
            'manager_name' => $row['manager_name'] ?? null,
            'reserved_at' => $row['reserved_at'],
            'expected_usage_date' => $row['expected_usage_date'] ?? null,
            'status' => $row['status'] ?? 'active',
            'source_type' => $row['source_type'] ?? null,
            'source_reference' => $row['source_reference'] ?? null,
        ]);

        return [
            'model_type' => Reservation::class,
            'model_id' => (int) $model->getKey(),
            'model' => $model,
        ];
    }
}
