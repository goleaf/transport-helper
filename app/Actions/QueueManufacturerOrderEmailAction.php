<?php

namespace App\Actions;

use App\Enums\SupplyOrderStatus;
use App\Mail\ManufacturerOrderRequestMail;
use App\Models\SupplyOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class QueueManufacturerOrderEmailAction
{
    public function __construct(public RecordSupplyAuditAction $recordSupplyAudit) {}

    public function handle(SupplyOrder $order, ?User $actor = null): SupplyOrder
    {
        $order->loadMissing(['manufacturer', 'product']);

        return DB::transaction(function () use ($actor, $order): SupplyOrder {
            Mail::to($order->manufacturer->email)->queue(new ManufacturerOrderRequestMail($order));

            $order->forceFill([
                'status' => SupplyOrderStatus::EmailQueued,
                'submitted_at' => now(),
            ])->save();

            $this->recordSupplyAudit->handle($actor, 'manufacturer.email_queued', $order, [
                'manufacturer_id' => $order->manufacturer_id,
                'email' => $order->manufacturer->email,
            ]);

            return $order->refresh()->load(['manufacturer', 'product']);
        });
    }
}
