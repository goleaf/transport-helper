<?php

namespace App\Services\Supply;

use App\Enums\LogisticsStatus;
use App\Enums\SupplierConfirmationStatus;
use App\Models\AuditLog;
use App\Models\CarrierQuote;
use App\Models\LogisticsRecord;
use App\Models\SupplierConfirmation;
use App\Models\SupplierOrder;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LogisticsRecordService
{
    public function __construct(
        private readonly LogisticsNotificationService $notificationService,
    ) {}

    public function ensureForSupplierOrder(SupplierOrder $supplierOrder, ?User $user = null): LogisticsRecord
    {
        return DB::transaction(function () use ($supplierOrder, $user): LogisticsRecord {
            $supplierOrder->loadMissing('supplier:id,default_currency');
            $record = LogisticsRecord::query()->firstOrCreate([
                'company_id' => $supplierOrder->company_id,
                'supplier_order_id' => $supplierOrder->id,
            ], [
                'supplier_id' => $supplierOrder->supplier_id,
                'order_date' => $supplierOrder->order_date,
                'status' => LogisticsStatus::Planned,
                'currency' => $supplierOrder->supplier?->default_currency,
            ]);

            $this->writeAuditLog('logistics_record.created_or_ensured', $record, $user, [], [
                'supplier_order_id' => $supplierOrder->id,
                'status' => $record->status,
            ]);

            if ($user instanceof User) {
                $this->notificationService->notifyDatabase(LogisticsNotificationService::OrderPrepared, [
                    'supplier_order_id' => $supplierOrder->id,
                    'logistics_record_id' => $record->id,
                ], [$user]);
            }

            return $record->refresh();
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function updateFromSupplierConfirmation(SupplierConfirmation $confirmation, ?User $user = null): array
    {
        return DB::transaction(function () use ($confirmation, $user): array {
            $confirmation->loadMissing(['supplierOrder.supplier', 'items']);
            $supplierOrder = $confirmation->supplierOrder;
            $record = $this->ensureForSupplierOrder($supplierOrder);
            $oldValues = $record->only(['confirmation_date', 'ready_date', 'pickup_date', 'delivery_date', 'status']);
            $notifications = [LogisticsNotificationService::SupplierConfirmationReceived];

            if ($confirmation->ready_date === null) {
                $notifications[] = LogisticsNotificationService::MissingReadyDate;
            }

            if ($this->isDelayed($record->ready_date, $confirmation->ready_date) || $this->isDelayed($record->delivery_date, $confirmation->expected_arrival_date)) {
                $notifications[] = LogisticsNotificationService::DateDelay;
            }

            if ($this->hasQuantityMismatch($confirmation)) {
                $notifications[] = LogisticsNotificationService::QuantityMismatch;
            }

            $record->forceFill([
                'confirmation_date' => $confirmation->confirmation_date ?? $record->confirmation_date,
                'ready_date' => $confirmation->ready_date ?? $record->ready_date,
                'pickup_date' => $confirmation->shipping_date ?? $record->pickup_date,
                'delivery_date' => $confirmation->expected_arrival_date ?? $record->delivery_date,
                'status' => $this->statusForConfirmation($confirmation),
            ])->save();

            $this->writeAuditLog('logistics_record.updated_from_supplier_confirmation', $record, $user, $oldValues, $record->only(['confirmation_date', 'ready_date', 'pickup_date', 'delivery_date', 'status']));
            $this->dispatchNotifications($notifications, [
                'supplier_order_id' => $supplierOrder->id,
                'supplier_confirmation_id' => $confirmation->id,
                'logistics_record_id' => $record->id,
            ], $user);

            return [
                'record' => $record->refresh(),
                'notifications' => array_values(array_unique($notifications)),
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function updateFromCarrierQuoteSelection(CarrierQuote $quote, User $user): array
    {
        return DB::transaction(function () use ($quote, $user): array {
            $quote->loadMissing('supplierOrder');
            $record = $this->ensureForSupplierOrder($quote->supplierOrder);
            $oldValues = $record->only(['carrier_id', 'pickup_date', 'delivery_date', 'transport_price', 'currency', 'status']);

            $record->forceFill([
                'carrier_id' => $quote->carrier_id,
                'pickup_date' => $quote->pickup_date ?? $record->pickup_date,
                'delivery_date' => $quote->delivery_date ?? $record->delivery_date,
                'transport_price' => $quote->price ?? $record->transport_price,
                'currency' => $quote->currency ?? $record->currency,
                'status' => LogisticsStatus::PickupScheduled,
            ])->save();

            $this->writeAuditLog('logistics_record.carrier_selected', $record, $user, $oldValues, $record->only(['carrier_id', 'pickup_date', 'delivery_date', 'transport_price', 'currency', 'status']));
            $this->notificationService->notifyDatabase(LogisticsNotificationService::CarrierSelected, [
                'supplier_order_id' => $quote->supplier_order_id,
                'carrier_quote_id' => $quote->id,
                'logistics_record_id' => $record->id,
            ], [$user]);

            return [
                'record' => $record->refresh(),
                'notifications' => [LogisticsNotificationService::CarrierSelected],
            ];
        });
    }

    public function updateStatus(LogisticsRecord $record, LogisticsStatus|string $status, User $user): LogisticsRecord
    {
        return DB::transaction(function () use ($record, $status, $user): LogisticsRecord {
            $status = $status instanceof LogisticsStatus ? $status : LogisticsStatus::from($status);
            $oldValues = $record->only(['status']);

            $record->forceFill(['status' => $status])->save();

            $this->writeAuditLog('logistics_record.status_updated', $record, $user, $oldValues, $record->only(['status']));

            if ($status === LogisticsStatus::Delayed) {
                $this->notificationService->notifyDatabase(LogisticsNotificationService::DateDelay, [
                    'logistics_record_id' => $record->id,
                    'supplier_order_id' => $record->supplier_order_id,
                ], [$user]);
            }

            if ($status === LogisticsStatus::Arrived) {
                $this->notificationService->notifyDatabase(LogisticsNotificationService::GoodsArrived, [
                    'logistics_record_id' => $record->id,
                    'supplier_order_id' => $record->supplier_order_id,
                ], [$user]);
            }

            return $record->refresh();
        });
    }

    private function isDelayed(mixed $oldDate, mixed $newDate): bool
    {
        if ($oldDate === null || $newDate === null) {
            return false;
        }

        return Carbon::parse((string) $newDate)->gt(Carbon::parse((string) $oldDate));
    }

    private function hasQuantityMismatch(SupplierConfirmation $confirmation): bool
    {
        if (in_array($confirmation->status, [
            SupplierConfirmationStatus::PartiallyConfirmed,
            SupplierConfirmationStatus::QuantityMismatch,
            SupplierConfirmationStatus::NeedsReview,
        ], true)) {
            return true;
        }

        return $confirmation->items->contains(
            fn ($item): bool => abs((float) $item->discrepancy_quantity) > 0.0001
                || ! in_array((string) $item->status, ['confirmed', 'matched'], true)
        );
    }

    private function statusForConfirmation(SupplierConfirmation $confirmation): LogisticsStatus
    {
        if ($confirmation->ready_date === null) {
            return LogisticsStatus::WaitingForReadyDate;
        }

        if (in_array($confirmation->status, [
            SupplierConfirmationStatus::NeedsReview,
            SupplierConfirmationStatus::QuantityMismatch,
            SupplierConfirmationStatus::DateMismatch,
            SupplierConfirmationStatus::Rejected,
        ], true)) {
            return LogisticsStatus::NeedsReview;
        }

        return LogisticsStatus::Confirmed;
    }

    /**
     * @param  list<string>  $events
     * @param  array<string, mixed>  $payload
     */
    private function dispatchNotifications(array $events, array $payload, ?User $user): void
    {
        foreach (array_values(array_unique($events)) as $event) {
            $this->notificationService->notifyDatabase($event, $payload, $user instanceof User ? [$user] : null);
        }
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    private function writeAuditLog(string $eventType, LogisticsRecord $record, ?User $user, array $oldValues, array $newValues): void
    {
        AuditLog::query()->create([
            'company_id' => $record->company_id,
            'user_id' => $user?->id,
            'event_type' => $eventType,
            'auditable_type' => $record::class,
            'auditable_id' => $record->id,
            'old_values_json' => $oldValues,
            'new_values_json' => $newValues,
            'metadata_json' => [
                'supplier_order_id' => $record->supplier_order_id,
            ],
            'created_at' => now(),
        ]);
    }
}
