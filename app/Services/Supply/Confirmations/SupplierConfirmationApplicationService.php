<?php

namespace App\Services\Supply\Confirmations;

use App\Enums\FormAutofillRunStatus;
use App\Enums\SupplierConfirmationStatus;
use App\Enums\SupplierOrderStatus;
use App\Events\SupplierConfirmationApplied;
use App\Models\AiEmailExtraction;
use App\Models\FormAutofillRun;
use App\Models\SupplierConfirmation;
use App\Models\SupplierOrder;
use App\Models\SupplierOrderItem;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupplierConfirmationApplicationService
{
    public function __construct(
        private readonly SupplierConfirmationItemMatcher $itemMatcher,
        private readonly SupplierConfirmationDiscrepancyService $discrepancyService,
        private readonly SupplierConfirmationStatusResolver $statusResolver,
        private readonly SupplierConfirmationInboundUpdater $inboundUpdater,
        private readonly SupplierConfirmationLogisticsUpdater $logisticsUpdater,
        private readonly SupplierConfirmationRiskService $riskService,
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * @param  array<string, mixed>  $normalizedConfirmation
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function apply(SupplierOrder $order, array $normalizedConfirmation, User $user, array $options = []): array
    {
        $this->validateApplication($order, $normalizedConfirmation, $user, $options);

        return DB::transaction(function () use ($order, $normalizedConfirmation, $user, $options): array {
            $order->loadMissing('items.product.supplierProductRules', 'supplier', 'logisticsRecords');
            $matchedItems = $this->matchItems($order, $normalizedConfirmation);
            $discrepancyResult = $this->discrepancyService->detect($order, $matchedItems, $normalizedConfirmation);
            $statuses = $this->statusResolver->resolve($discrepancyResult, $matchedItems, $normalizedConfirmation);
            $oldOrderStatus = $this->statusValue($order->status);
            $matchedForPersistence = array_values(array_filter(
                $matchedItems,
                fn (array $item): bool => ($item['matched'] ?? false) === true
            ));

            $confirmation = SupplierConfirmation::query()->create([
                'company_id' => $order->company_id,
                'supplier_order_id' => $order->getKey(),
                'email_message_id' => $normalizedConfirmation['email_message_id'] ?? null,
                'supplier_reference' => $normalizedConfirmation['supplier_reference'] ?? null,
                'confirmation_date' => $normalizedConfirmation['confirmation_date'] ?? now()->toDateString(),
                'ready_date' => $normalizedConfirmation['ready_date'] ?? null,
                'shipping_date' => $normalizedConfirmation['shipping_date'] ?? null,
                'expected_arrival_date' => $normalizedConfirmation['expected_arrival_date'] ?? null,
                'status' => $statuses['supplier_confirmation_status'],
                'discrepancy_summary' => $discrepancyResult['summary'],
                'created_from_ai_extraction_id' => ($normalizedConfirmation['source_type'] ?? null) === 'ai_email_extraction'
                    ? ($normalizedConfirmation['source_id'] ?? null)
                    : null,
                'created_from_form_autofill_run_id' => ($normalizedConfirmation['source_type'] ?? null) === 'form_autofill_run'
                    ? ($normalizedConfirmation['source_id'] ?? null)
                    : null,
                'source_type' => $normalizedConfirmation['source_type'] ?? 'manual',
                'source_id' => $normalizedConfirmation['source_id'] ?? null,
                'output_json' => $normalizedConfirmation['raw'] ?? $normalizedConfirmation,
                'discrepancies_json' => $discrepancyResult['discrepancies'],
                'applied_by_user_id' => $user->getKey(),
                'applied_at' => now(),
            ]);
            $this->auditLogService->write('supplier_confirmation_created', $confirmation, $user, null, null, [
                'supplier_confirmation_id' => $confirmation->getKey(),
                'supplier_order_id' => $order->getKey(),
                'source_type' => $confirmation->source_type,
                'source_id' => $confirmation->source_id,
                'status' => $this->statusValue($confirmation->status),
            ], $order->company_id);

            foreach (array_keys($matchedForPersistence) as $index) {
                $this->createConfirmationItem($confirmation, $matchedForPersistence[$index], $discrepancyResult, $user);
                $this->updateSupplierOrderItem($matchedForPersistence[$index], $user, $discrepancyResult);
            }

            $order->forceFill(['status' => $statuses['supplier_order_status']])->save();
            $this->auditLogService->write('supplier_order_status_changed', $order, $user, [
                'status' => $oldOrderStatus,
            ], [
                'status' => $this->statusValue($order->status),
            ], [
                'supplier_confirmation_id' => $confirmation->getKey(),
            ], $order->company_id);

            $inboundResult = null;

            if (($options['update_inbound'] ?? true) === true) {
                $inboundResult = $this->inboundUpdater->updateInbound($order->refresh(), $confirmation, $matchedForPersistence, $options);
                $this->auditLogService->write('inbound_order_updated', $inboundResult['inbound_order'], $user, null, null, [
                    'supplier_confirmation_id' => $confirmation->getKey(),
                    'items_count' => $inboundResult['items_count'],
                ], $order->company_id);
            }

            $logisticsResult = null;

            if (($options['update_logistics'] ?? true) === true) {
                $logisticsResult = $this->logisticsUpdater->updateLogistics($order->refresh(), $confirmation, $statuses['logistics_status'], $options);
                $this->auditLogService->write('logistics_record_updated', $logisticsResult['logistics_record'], $user, null, null, [
                    'supplier_confirmation_id' => $confirmation->getKey(),
                    'status' => $this->statusValue($statuses['logistics_status']),
                ], $order->company_id);
            }

            $riskResult = $this->riskService->handleRisk($confirmation, $discrepancyResult, $user);
            $this->markSourceApplied($normalizedConfirmation, $confirmation, $user);

            if ($confirmation->status === SupplierConfirmationStatus::NeedsReview) {
                $this->auditLogService->write('supplier_confirmation_needs_review', $confirmation, $user, null, null, [
                    'supplier_confirmation_id' => $confirmation->getKey(),
                    'discrepancies' => $discrepancyResult['discrepancies'],
                ], $order->company_id);
            }

            $this->auditLogService->write('supplier_confirmation_applied', $confirmation, $user, null, null, [
                'supplier_confirmation_id' => $confirmation->getKey(),
                'supplier_order_id' => $order->getKey(),
                'source_type' => $confirmation->source_type,
                'source_id' => $confirmation->source_id,
                'status' => $this->statusValue($confirmation->status),
                'discrepancy_count' => count($discrepancyResult['discrepancies']),
                'has_blocking_discrepancies' => $discrepancyResult['blocking'],
                'applied_by_user_id' => $user->getKey(),
            ], $order->company_id);

            SupplierConfirmationApplied::dispatch($confirmation);

            return [
                'supplier_order' => $order->refresh(),
                'confirmation' => $confirmation->load('items.product', 'supplierOrder'),
                'discrepancies' => $discrepancyResult['discrepancies'],
                'discrepancy_result' => $discrepancyResult,
                'inbound' => $inboundResult,
                'inbound_order' => $inboundResult['inbound_order'] ?? null,
                'logistics' => $logisticsResult,
                'logistics_record' => $logisticsResult['logistics_record'] ?? null,
                'risk' => $riskResult,
                'risk_flagged' => $riskResult['risk_flagged'],
                'risk_reasons' => $riskResult['risk_reasons'],
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $normalizedConfirmation
     * @param  array<string, mixed>  $options
     */
    private function validateApplication(SupplierOrder $order, array $normalizedConfirmation, User $user, array $options): void
    {
        if (! ($user->can('createManual', SupplierConfirmation::class) || $user->can('apply', SupplierConfirmation::class) || $user->canManageSupplyWorkflow())) {
            throw ValidationException::withMessages(['user' => 'User cannot apply supplier confirmations.']);
        }

        if (in_array($order->status, [SupplierOrderStatus::Cancelled, SupplierOrderStatus::Completed], true)) {
            throw ValidationException::withMessages(['supplier_order' => 'Cancelled or completed supplier orders cannot be confirmed.']);
        }

        $hasItems = is_array($normalizedConfirmation['items'] ?? null) && $normalizedConfirmation['items'] !== [];
        $hasDate = collect(['confirmation_date', 'ready_date', 'shipping_date', 'expected_arrival_date'])
            ->contains(fn (string $key): bool => ! empty($normalizedConfirmation[$key]));

        if (! $hasItems && ! $hasDate) {
            throw ValidationException::withMessages(['confirmation' => 'At least one item or date is required.']);
        }

        if (($normalizedConfirmation['source_id'] ?? null) !== null && ($options['reapply_allowed'] ?? false) !== true) {
            $exists = SupplierConfirmation::query()
                ->where('source_type', $normalizedConfirmation['source_type'] ?? null)
                ->where('source_id', $normalizedConfirmation['source_id'])
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages(['source' => 'This supplier confirmation source has already been applied.']);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $normalizedConfirmation
     * @return list<array<string, mixed>>
     */
    private function matchItems(SupplierOrder $order, array $normalizedConfirmation): array
    {
        return collect($normalizedConfirmation['items'] ?? [])
            ->filter(fn (mixed $item): bool => is_array($item))
            ->map(function (array $item) use ($order): array {
                $match = $this->itemMatcher->match($order, $item);

                return $match + [
                    'source_item' => $item,
                    'confirmed_quantity' => $item['confirmed_quantity'] ?? null,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $matchedItem
     * @param  array<string, mixed>  $discrepancyResult
     */
    private function createConfirmationItem(SupplierConfirmation $confirmation, array $matchedItem, array $discrepancyResult, User $user): void
    {
        $orderItem = $matchedItem['supplier_order_item'];
        $confirmedQuantity = $matchedItem['confirmed_quantity'];
        $orderedQuantity = (float) $orderItem->ordered_quantity;
        $itemDiscrepancies = $this->itemDiscrepancies($orderItem, $discrepancyResult);
        $itemStatus = $this->itemStatus($itemDiscrepancies, $confirmedQuantity, $orderedQuantity);

        $confirmationItem = $confirmation->items()->create([
            'product_id' => $orderItem->product_id,
            'ordered_quantity' => $orderedQuantity,
            'confirmed_quantity' => $confirmedQuantity,
            'discrepancy_quantity' => $confirmedQuantity === null ? null : ((float) $confirmedQuantity - $orderedQuantity),
            'status' => $itemStatus,
            'notes' => $matchedItem['source_item']['notes'] ?? null,
            'source_item_json' => $matchedItem['source_item'],
            'matched_by' => $matchedItem['matched_by'],
            'discrepancy_type' => $itemDiscrepancies[0]['type'] ?? null,
            'discrepancies_json' => $itemDiscrepancies,
        ]);

        $this->auditLogService->write('supplier_confirmation_item_created', $confirmationItem, $user, null, null, [
            'supplier_confirmation_id' => $confirmation->getKey(),
            'supplier_order_item_id' => $orderItem->getKey(),
            'product_id' => $orderItem->product_id,
            'matched_by' => $matchedItem['matched_by'],
        ], $confirmation->company_id);

        $matchedItem['item_status'] = $itemStatus;
    }

    /**
     * @param  array<string, mixed>  $matchedItem
     * @param  array<string, mixed>  $discrepancyResult
     */
    private function updateSupplierOrderItem(array &$matchedItem, User $user, array $discrepancyResult): void
    {
        $orderItem = $matchedItem['supplier_order_item'];
        $oldValues = $orderItem->only(['confirmed_quantity', 'received_quantity', 'status']);
        $itemDiscrepancies = $this->itemDiscrepancies($orderItem, $discrepancyResult);
        $itemStatus = $this->itemStatus($itemDiscrepancies, $matchedItem['confirmed_quantity'], (float) $orderItem->ordered_quantity);
        $matchedItem['item_status'] = $itemStatus;

        $orderItem->forceFill([
            'confirmed_quantity' => $matchedItem['confirmed_quantity'],
            'status' => $itemStatus,
        ])->save();

        $this->auditLogService->write(
            $itemDiscrepancies === [] ? 'supplier_order_item_confirmed' : 'supplier_order_item_confirmation_mismatch',
            $orderItem,
            $user,
            $oldValues,
            $orderItem->only(['confirmed_quantity', 'received_quantity', 'status']),
            [
                'product_id' => $orderItem->product_id,
                'sku' => $orderItem->product?->sku,
                'ordered_quantity' => (float) $orderItem->ordered_quantity,
                'confirmed_quantity' => $matchedItem['confirmed_quantity'],
                'discrepancy_type' => $itemDiscrepancies[0]['type'] ?? null,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $discrepancyResult
     * @return list<array<string, mixed>>
     */
    private function itemDiscrepancies(SupplierOrderItem $orderItem, array $discrepancyResult): array
    {
        return collect($discrepancyResult['discrepancies'] ?? [])
            ->filter(fn (array $discrepancy): bool => (int) ($discrepancy['product_id'] ?? 0) === (int) $orderItem->product_id)
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $itemDiscrepancies
     */
    private function itemStatus(array $itemDiscrepancies, mixed $confirmedQuantity, float $orderedQuantity): string
    {
        $types = collect($itemDiscrepancies)->pluck('type')->all();

        if (in_array('quantity_higher_than_ordered', $types, true) || in_array('missing_confirmed_quantity', $types, true)) {
            return 'needs_review';
        }

        if (in_array('quantity_lower_than_ordered', $types, true)) {
            return 'partially_confirmed';
        }

        if ($confirmedQuantity !== null && (float) $confirmedQuantity === $orderedQuantity) {
            return 'confirmed';
        }

        return 'quantity_mismatch';
    }

    /**
     * @param  array<string, mixed>  $normalizedConfirmation
     */
    private function markSourceApplied(array $normalizedConfirmation, SupplierConfirmation $confirmation, User $user): void
    {
        if (($normalizedConfirmation['source_type'] ?? null) === 'form_autofill_run' && isset($normalizedConfirmation['source_id'])) {
            $run = FormAutofillRun::query()->find($normalizedConfirmation['source_id']);

            if ($run instanceof FormAutofillRun) {
                $oldValues = $run->only(['status', 'applied_by_user_id', 'applied_at']);
                $run->forceFill([
                    'status' => FormAutofillRunStatus::Applied,
                    'applied_by_user_id' => $user->getKey(),
                    'applied_at' => now(),
                ])->save();
                $this->auditLogService->write('form_autofill_run_applied', $run, $user, $oldValues, $run->only(['status', 'applied_by_user_id', 'applied_at']), [
                    'supplier_confirmation_id' => $confirmation->getKey(),
                ], $run->company_id);
            }
        }

        if (($normalizedConfirmation['source_type'] ?? null) === 'ai_email_extraction' && isset($normalizedConfirmation['source_id'])) {
            $extraction = AiEmailExtraction::query()->find($normalizedConfirmation['source_id']);

            if ($extraction instanceof AiEmailExtraction) {
                $output = is_array($extraction->output_json) ? $extraction->output_json : [];
                $output['_applied_supplier_confirmation_id'] = $confirmation->getKey();
                $extraction->forceFill(['output_json' => $output])->save();
                $this->auditLogService->write('ai_extraction_applied_to_supplier_confirmation', $extraction, $user, null, null, [
                    'supplier_confirmation_id' => $confirmation->getKey(),
                    'email_message_id' => $extraction->email_message_id,
                    'extraction_id' => $extraction->getKey(),
                ]);
            }
        }

        if (($normalizedConfirmation['source_type'] ?? null) === 'manual') {
            $this->auditLogService->write('manual_supplier_confirmation_applied', $confirmation, $user, null, null, [
                'supplier_confirmation_id' => $confirmation->getKey(),
            ], $confirmation->company_id);
        }
    }

    private function statusValue(mixed $status): string
    {
        return $status instanceof \BackedEnum ? $status->value : (string) $status;
    }
}
