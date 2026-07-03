<?php

namespace App\Services\Supply;

use App\Models\AuditLog;
use App\Models\Carrier;
use App\Models\SupplierOrder;
use App\Models\User;
use Illuminate\Support\Collection;

class CarrierQuoteRequestService
{
    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function requestQuotes(SupplierOrder $supplierOrder, array $input = [], ?User $user = null): array
    {
        $carrierIds = is_array($input['carrier_ids'] ?? null) ? $input['carrier_ids'] : [];
        $carriers = $this->carriers($supplierOrder, $carrierIds);

        AuditLog::query()->create([
            'company_id' => $supplierOrder->company_id,
            'user_id' => $user?->id,
            'event_type' => 'carrier_quote.requested',
            'auditable_type' => $supplierOrder::class,
            'auditable_id' => $supplierOrder->id,
            'old_values_json' => [],
            'new_values_json' => [
                'carrier_ids' => $carriers->pluck('id')->all(),
                'required_pickup_date' => $input['required_pickup_date'] ?? null,
                'required_delivery_date' => $input['required_delivery_date'] ?? null,
                'message' => $input['message'] ?? null,
            ],
            'metadata_json' => [],
            'created_at' => now(),
        ]);

        return [
            'supplier_order' => $supplierOrder,
            'carriers' => $carriers,
            'requested_count' => $carriers->count(),
        ];
    }

    /**
     * @param  list<int|string>  $carrierIds
     * @return Collection<int, Carrier>
     */
    private function carriers(SupplierOrder $supplierOrder, array $carrierIds): Collection
    {
        $query = Carrier::query()
            ->select(['id', 'company_id', 'name', 'code', 'default_currency', 'reliability_score', 'is_active'])
            ->where('company_id', $supplierOrder->company_id)
            ->where('is_active', true)
            ->orderBy('name');

        if ($carrierIds !== []) {
            $query->whereIn('id', $carrierIds);
        }

        return $query->get();
    }
}
