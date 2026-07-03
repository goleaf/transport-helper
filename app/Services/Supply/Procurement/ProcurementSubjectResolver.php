<?php

namespace App\Services\Supply\Procurement;

use App\Models\CalculationScenario;
use App\Models\OrderProposal;
use App\Models\SupplierOrder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class ProcurementSubjectResolver
{
    public function resolve(string $type, int $id): Model
    {
        return match ($type) {
            'proposal' => OrderProposal::query()->findOrFail($id),
            'supplier_order' => SupplierOrder::query()->findOrFail($id),
            'scenario' => CalculationScenario::query()->findOrFail($id),
            default => throw new InvalidArgumentException('Unsupported procurement subject type.'),
        };
    }
}
