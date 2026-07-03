<?php

namespace App\Actions;

use App\Enums\LogisticsStatus;
use App\Enums\SupplyOrderStatus;
use App\Models\LogisticsEntry;
use App\Models\SupplyOrder;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class CompareTransportOptionsAction
{
    public function __construct(public RecordSupplyAuditAction $recordSupplyAudit) {}

    public function handle(SupplyOrder $order, ?User $actor = null): LogisticsEntry
    {
        $selectedOption = $order->logisticsOptions()
            ->orderBy('price_cents')
            ->orderBy('delivery_on')
            ->orderBy('transit_days')
            ->first();

        if ($selectedOption === null) {
            throw new DomainException('At least one transport option is required before logistics can be updated.');
        }

        return DB::transaction(function () use ($actor, $order, $selectedOption): LogisticsEntry {
            $order->logisticsOptions()->update(['selected' => false]);

            $selectedOption->forceFill(['selected' => true])->save();

            $entry = LogisticsEntry::query()->updateOrCreate(
                ['supply_order_id' => $order->getKey()],
                [
                    'logistics_option_id' => $selectedOption->getKey(),
                    'updated_by_id' => $actor?->getKey(),
                    'carrier_name' => $selectedOption->carrier_name,
                    'price_cents' => $selectedOption->price_cents,
                    'currency' => $selectedOption->currency,
                    'pickup_on' => $selectedOption->pickup_on,
                    'delivery_on' => $selectedOption->delivery_on,
                    'status' => LogisticsStatus::Planned,
                    'compared_at' => now(),
                ],
            );

            $order->forceFill([
                'status' => SupplyOrderStatus::LogisticsPlanned,
            ])->save();

            $this->recordSupplyAudit->handle($actor, 'logistics.option_selected', $order, [
                'logistics_option_id' => $selectedOption->getKey(),
                'carrier_name' => $selectedOption->carrier_name,
                'price_cents' => $selectedOption->price_cents,
                'delivery_on' => $selectedOption->delivery_on?->toDateString(),
            ]);

            return $entry->refresh();
        });
    }
}
