<?php

namespace App\Services\Supply\Logistics;

use App\Enums\LogisticsStatus;
use App\Models\LogisticsRecord;
use App\Models\SupplierOrder;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LogisticsRecordService
{
    public function __construct(
        private readonly LogisticsStatusResolver $statusResolver,
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createOrUpdateForSupplierOrder(SupplierOrder $order, array $data = [], ?User $user = null): array
    {
        return DB::transaction(function () use ($order, $data, $user): array {
            $order->loadMissing('supplier:id,default_currency');
            $record = LogisticsRecord::query()->firstOrNew([
                'company_id' => $order->company_id,
                'supplier_order_id' => $order->id,
            ]);
            $created = ! $record->exists;
            $oldValues = $record->exists ? $record->getOriginal() : [];

            $record->fill(array_filter([
                'supplier_id' => $order->supplier_id,
                'order_date' => $data['order_date'] ?? $order->order_date,
                'currency' => $data['currency'] ?? $record->currency ?? $order->supplier?->default_currency,
                'status' => $data['status'] ?? $record->status ?? LogisticsStatus::Planned,
            ], fn (mixed $value): bool => $value !== null));
            $record->fill(Arr::only($data, $this->updatableFields()));
            $record->save();

            $this->auditLogService->write(
                $created ? 'logistics_record_created' : 'logistics_record_updated',
                $record,
                $user,
                $oldValues,
                $record->getAttributes(),
                ['supplier_order_id' => $order->id],
                $record->company_id,
            );

            return [
                'record' => $record->refresh(),
                'created' => $created,
                'suggestion' => $this->statusResolver->suggestStatus($record),
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public function manualUpdate(LogisticsRecord $record, array $validated, User $user): array
    {
        $changes = Arr::only($validated, $this->updatableFields());
        $oldValues = $record->only(array_keys($changes));

        if ($this->hasMeaningfulChange($record, $changes) && blank($validated['reason'] ?? null)) {
            throw ValidationException::withMessages(['reason' => 'A reason is required when logistics values change.']);
        }

        $this->validateDateOrder($record, $changes, (bool) ($validated['override_date_conflicts'] ?? false));

        return DB::transaction(function () use ($record, $changes, $validated, $user, $oldValues): array {
            $record->fill($changes);
            $record->save();

            $newValues = $record->only(array_keys($changes));
            $metadata = [
                'logistics_record_id' => $record->id,
                'reason' => $validated['reason'] ?? null,
                'override_date_conflicts' => (bool) ($validated['override_date_conflicts'] ?? false),
            ];

            $this->auditLogService->write('logistics_manual_update', $record, $user, $oldValues, $newValues, $metadata, $record->company_id);
            $this->auditLogService->write('logistics_record_updated', $record, $user, $oldValues, $newValues, $metadata, $record->company_id);

            return [
                'record' => $record->refresh(),
                'warnings' => $this->dateWarnings($record),
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function updateStatus(LogisticsRecord $record, string $status, string $reason, ?User $user = null): array
    {
        if (blank($reason)) {
            throw ValidationException::withMessages(['reason' => 'A reason is required when logistics status changes.']);
        }

        $oldStatus = $record->status instanceof LogisticsStatus ? $record->status->value : (string) $record->status;
        $record->forceFill(['status' => LogisticsStatus::from($status)])->save();

        $this->auditLogService->write('logistics_status_changed', $record, $user, ['status' => $oldStatus], ['status' => $status], [
            'reason' => $reason,
        ], $record->company_id);

        return [
            'record' => $record->refresh(),
        ];
    }

    /**
     * @return list<string>
     */
    private function updatableFields(): array
    {
        return [
            'order_date',
            'confirmation_date',
            'ready_date',
            'pickup_date',
            'delivery_date',
            'actual_received_date',
            'carrier_id',
            'transport_price',
            'currency',
            'status',
            'notes',
        ];
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    private function hasMeaningfulChange(LogisticsRecord $record, array $changes): bool
    {
        foreach ($changes as $key => $value) {
            if ($this->scalar($record->getAttribute($key)) !== $this->scalar($value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    private function validateDateOrder(LogisticsRecord $record, array $changes, bool $override): void
    {
        if ($override) {
            return;
        }

        $pickup = $changes['pickup_date'] ?? $record->pickup_date?->toDateString();
        $delivery = $changes['delivery_date'] ?? $record->delivery_date?->toDateString();

        if ($pickup !== null && $delivery !== null && strtotime((string) $delivery) < strtotime((string) $pickup)) {
            throw ValidationException::withMessages(['delivery_date' => 'Delivery date cannot be before pickup date.']);
        }
    }

    /**
     * @return list<string>
     */
    private function dateWarnings(LogisticsRecord $record): array
    {
        $warnings = [];

        if ($record->ready_date !== null && $record->pickup_date !== null && $record->pickup_date->lt($record->ready_date)) {
            $warnings[] = 'pickup_before_ready_date';
        }

        if ($record->actual_received_date !== null && $record->delivery_date !== null && $record->actual_received_date->lt($record->delivery_date)) {
            $warnings[] = 'actual_receipt_before_delivery_date';
        }

        return $warnings;
    }

    private function scalar(mixed $value): string
    {
        if ($value instanceof \BackedEnum) {
            return (string) $value->value;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        return (string) $value;
    }
}
