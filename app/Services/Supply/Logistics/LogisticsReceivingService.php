<?php

namespace App\Services\Supply\Logistics;

use App\Enums\LogisticsStatus;
use App\Enums\SupplierOrderStatus;
use App\Models\InboundOrderItem;
use App\Models\SupplierOrder;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LogisticsReceivingService
{
    public function __construct(
        private readonly LogisticsReceivingDiscrepancyService $discrepancyService,
        private readonly LogisticsRecordService $recordService,
        private readonly LogisticsNotificationService $notificationService,
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public function recordReceipt(SupplierOrder $order, array $validated, User $user): array
    {
        $order->loadMissing('items.product', 'logisticsRecords');

        if ($order->status === SupplierOrderStatus::Cancelled) {
            throw ValidationException::withMessages(['supplier_order' => 'Cancelled supplier orders cannot receive goods.']);
        }

        $items = array_values($validated['items'] ?? []);
        $completeOrder = (bool) ($validated['complete_order'] ?? true);
        $discrepancies = $this->discrepancyService->detect($order, $items, ['complete_order' => $completeOrder]);

        if ($discrepancies['blocking'] && ! (bool) ($validated['confirm_mismatches'] ?? false)) {
            throw ValidationException::withMessages([
                'confirm_mismatches' => $discrepancies['summary'],
            ]);
        }

        return DB::transaction(function () use ($order, $validated, $user, $items, $completeOrder, $discrepancies): array {
            $recordResult = $this->recordService->createOrUpdateForSupplierOrder($order, [], $user);
            $record = $recordResult['record'];
            $receivedCount = 0;

            foreach ($items as $receivedItem) {
                $orderItem = $this->discrepancyService->matchOrderItem($order, $receivedItem);

                if ($orderItem === null) {
                    continue;
                }

                $oldValues = $orderItem->only(['received_quantity', 'damaged_quantity', 'receiving_notes', 'status']);
                $orderItem->forceFill([
                    'received_quantity' => $receivedItem['received_quantity'],
                    'damaged_quantity' => $receivedItem['damaged_quantity'] ?? null,
                    'receiving_notes' => $receivedItem['notes'] ?? null,
                    'status' => $discrepancies['has_discrepancies'] ? 'needs_review' : 'received',
                ])->save();
                $receivedCount++;

                $this->auditLogService->write('supplier_order_item_received', $orderItem, $user, $oldValues, $orderItem->only(['received_quantity', 'damaged_quantity', 'receiving_notes', 'status']), [
                    'supplier_order_id' => $order->id,
                ]);

                $this->updateInboundOrderItem($order, $orderItem->product_id, $receivedItem, $user);
            }

            $status = $this->receiptStatus($discrepancies['has_discrepancies'], $completeOrder);
            $oldRecordValues = $record->only(['actual_received_date', 'receiving_discrepancies_json', 'received_by_user_id', 'received_at', 'status', 'notes']);
            $record->forceFill([
                'actual_received_date' => $validated['actual_received_date'],
                'receiving_discrepancies_json' => $discrepancies['discrepancies'],
                'received_by_user_id' => $user->id,
                'received_at' => now(),
                'status' => $status,
                'notes' => trim((string) $record->notes."\n".(string) ($validated['notes'] ?? '')),
            ])->save();

            $oldOrderStatus = $order->status instanceof SupplierOrderStatus ? $order->status->value : (string) $order->status;
            $order->forceFill([
                'status' => $discrepancies['has_discrepancies']
                    ? SupplierOrderStatus::NeedsReview
                    : ($completeOrder ? SupplierOrderStatus::Completed : $order->status),
            ])->save();

            $this->auditLogService->write('goods_receipt_recorded', $record, $user, $oldRecordValues, $record->only(['actual_received_date', 'receiving_discrepancies_json', 'received_by_user_id', 'received_at', 'status', 'notes']), [
                'supplier_order_id' => $order->id,
                'items_count' => $receivedCount,
                'discrepancies' => $discrepancies['discrepancies'],
                'completed' => $status === LogisticsStatus::Completed,
            ], $record->company_id);
            $this->auditLogService->write('logistics_record_updated', $record, $user, $oldRecordValues, $record->getAttributes(), [
                'reason' => 'goods_receipt_recorded',
            ], $record->company_id);

            if ($oldOrderStatus !== ($order->status instanceof SupplierOrderStatus ? $order->status->value : (string) $order->status)) {
                $this->auditLogService->write('supplier_order_status_changed', $order, $user, ['status' => $oldOrderStatus], ['status' => $order->status], [
                    'source' => 'goods_receipt',
                ], $order->company_id);
            }

            $this->notifyReceipt($record, $discrepancies, $user);

            return [
                'record' => $record->refresh(),
                'order' => $order->refresh(),
                'discrepancies' => $discrepancies,
                'status' => $status->value,
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $receivedItem
     */
    private function updateInboundOrderItem(SupplierOrder $order, int $productId, array $receivedItem, User $user): void
    {
        $inboundItem = InboundOrderItem::query()
            ->where('product_id', $productId)
            ->whereHas('inboundOrder', fn ($query) => $query->where('supplier_order_id', $order->id))
            ->first();

        if (! $inboundItem instanceof InboundOrderItem) {
            return;
        }

        $oldValues = $inboundItem->only(['received_quantity', 'damaged_quantity', 'receiving_notes', 'status']);
        $inboundItem->forceFill([
            'received_quantity' => $receivedItem['received_quantity'],
            'damaged_quantity' => $receivedItem['damaged_quantity'] ?? null,
            'receiving_notes' => $receivedItem['notes'] ?? null,
            'status' => 'received',
        ])->save();

        $this->auditLogService->write('inbound_order_item_received', $inboundItem, $user, $oldValues, $inboundItem->only(['received_quantity', 'damaged_quantity', 'receiving_notes', 'status']), [
            'supplier_order_id' => $order->id,
        ]);
    }

    private function receiptStatus(bool $hasDiscrepancies, bool $completeOrder): LogisticsStatus
    {
        if ($hasDiscrepancies) {
            return LogisticsStatus::NeedsReview;
        }

        return $completeOrder ? LogisticsStatus::Completed : LogisticsStatus::Arrived;
    }

    /**
     * @param  array<string, mixed>  $discrepancies
     */
    private function notifyReceipt(mixed $record, array $discrepancies, User $user): void
    {
        if ($discrepancies['has_discrepancies']) {
            $this->auditLogService->write('receiving_mismatch_detected', $record, $user, null, null, [
                'discrepancies' => $discrepancies['discrepancies'],
            ], $record->company_id);
            $this->notificationService->notify('receiving_mismatch', [
                'company_id' => $record->company_id,
                'title' => 'Receiving mismatch',
                'message' => $discrepancies['summary'],
                'unique_key' => 'receiving-mismatch-'.$record->id,
                'logistics_record_id' => $record->id,
            ], ['user' => $user]);

            return;
        }

        $this->notificationService->notify('goods_received_completed', [
            'company_id' => $record->company_id,
            'title' => 'Goods receipt completed',
            'message' => 'Goods were received and reconciled.',
            'unique_key' => 'goods-received-'.$record->id,
            'logistics_record_id' => $record->id,
        ], ['user' => $user]);
    }
}
