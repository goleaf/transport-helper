<?php

namespace App\Services\Supply;

use App\Enums\FormAutofillRunStatus;
use App\Enums\LogisticsStatus;
use App\Enums\SupplierConfirmationStatus;
use App\Enums\SupplierOrderStatus;
use App\Jobs\RecalculateSupplyRiskJob;
use App\Models\AiEmailExtraction;
use App\Models\AuditLog;
use App\Models\FormAutofillRun;
use App\Models\InboundOrder;
use App\Models\LogisticsRecord;
use App\Models\SupplierConfirmation;
use App\Models\SupplierOrder;
use App\Models\SupplierOrderItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class SupplierConfirmationApplicationService
{
    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function apply(array $input): array
    {
        return DB::transaction(function () use ($input): array {
            $supplierOrder = SupplierOrder::query()
                ->with(['items.product.supplierProductRules', 'logisticsRecords'])
                ->findOrFail((int) ($input['supplier_order_id'] ?? 0));

            $source = $this->source($input);
            $confirmationData = $this->confirmationData($input, $source);
            $dates = $this->dates($confirmationData);
            $discrepancies = [];
            $riskReasons = [];

            foreach ($dates['discrepancies'] as $dateDiscrepancy) {
                $discrepancies[] = $dateDiscrepancy;
            }

            $matched = $this->matchedConfirmationItems($supplierOrder, $confirmationData, $discrepancies);
            $this->collectLogisticsDateDiscrepancies($supplierOrder, $dates, $discrepancies, $riskReasons);
            $status = $this->statusFor($matched, $discrepancies);
            $oldOrderValues = $supplierOrder->only(['status']);

            $confirmation = SupplierConfirmation::query()->create([
                'company_id' => $supplierOrder->company_id,
                'supplier_order_id' => $supplierOrder->id,
                'email_message_id' => $source['email_message_id'],
                'supplier_reference' => $confirmationData['supplier_reference'] ?? null,
                'confirmation_date' => $dates['confirmation_date'] ?? now()->toDateString(),
                'ready_date' => $dates['ready_date'],
                'shipping_date' => $dates['shipping_date'],
                'expected_arrival_date' => $dates['expected_arrival_date'],
                'status' => $this->confirmationStatusFor($status),
                'discrepancy_summary' => $discrepancies === [] ? null : json_encode($discrepancies, JSON_UNESCAPED_SLASHES),
                'created_from_ai_extraction_id' => $source['ai_email_extraction_id'],
                'created_from_form_autofill_run_id' => $source['form_autofill_run_id'],
            ]);

            foreach ($matched['items'] as $matchedItem) {
                $orderItem = $matchedItem['order_item'];
                $confirmedQuantity = $matchedItem['confirmed_quantity'];
                $orderedQuantity = (float) $orderItem->ordered_quantity;

                $confirmation->items()->create([
                    'product_id' => $orderItem->product_id,
                    'ordered_quantity' => $orderedQuantity,
                    'confirmed_quantity' => $confirmedQuantity,
                    'discrepancy_quantity' => $confirmedQuantity - $orderedQuantity,
                    'status' => $matchedItem['status'],
                    'notes' => $matchedItem['notes'],
                ]);

                $orderItem->forceFill([
                    'confirmed_quantity' => $confirmedQuantity,
                    'status' => $matchedItem['status'],
                ])->save();
            }

            foreach ($matched['missing_items'] as $missingItem) {
                $orderedQuantity = (float) $missingItem->ordered_quantity;

                $confirmation->items()->create([
                    'product_id' => $missingItem->product_id,
                    'ordered_quantity' => $orderedQuantity,
                    'confirmed_quantity' => 0,
                    'discrepancy_quantity' => -$orderedQuantity,
                    'status' => 'missing_item',
                    'notes' => null,
                ]);

                $missingItem->forceFill([
                    'confirmed_quantity' => 0,
                    'status' => 'missing_item',
                ])->save();
            }

            $supplierOrder->forceFill([
                'status' => $status,
            ])->save();

            $inboundOrders = $this->updateInboundOrders($supplierOrder, $matched, $dates, $status);
            $logisticsRecord = $this->updateLogisticsRecord($supplierOrder, $dates, $status);
            $riskReasons = array_values(array_unique(array_merge($riskReasons, $this->riskReasonsFromDiscrepancies($discrepancies))));

            $this->writeAuditLog($supplierOrder, $confirmation, (int) ($input['applied_by_user_id'] ?? 0), [
                'supplier_order' => $oldOrderValues,
            ], [
                'supplier_order' => $supplierOrder->only(['status']),
                'supplier_confirmation_id' => $confirmation->id,
                'status' => $status->value,
                'discrepancies' => $discrepancies,
                'risk_recalculation_reasons' => $riskReasons,
            ]);

            if ($riskReasons !== []) {
                RecalculateSupplyRiskJob::dispatch($supplierOrder->id, $confirmation->id, $riskReasons)->afterCommit();
            }

            return [
                'supplier_order' => $supplierOrder->refresh(),
                'confirmation' => $confirmation->load('items.product'),
                'discrepancies' => $discrepancies,
                'inbound_orders' => $inboundOrders,
                'logistics_record' => $logisticsRecord,
                'risk_recalculation_triggered' => $riskReasons !== [],
                'risk_recalculation_reasons' => $riskReasons,
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{ai_email_extraction_id:?int,form_autofill_run_id:?int,email_message_id:?int,form_values:array<string, mixed>,ai_values:array<string, mixed>}
     */
    private function source(array $input): array
    {
        $aiExtraction = isset($input['ai_email_extraction_id'])
            ? AiEmailExtraction::query()->with('emailMessage:id')->find($input['ai_email_extraction_id'])
            : null;
        $formRun = isset($input['form_autofill_run_id'])
            ? FormAutofillRun::query()->with(['emailMessage:id', 'fieldValues'])->find($input['form_autofill_run_id'])
            : null;

        if ($formRun instanceof FormAutofillRun && $formRun->status !== FormAutofillRunStatus::Validated && $formRun->status !== FormAutofillRunStatus::Applied) {
            throw ValidationException::withMessages([
                'form_autofill_run_id' => 'Form autofill run must be validated before confirmation can be applied.',
            ]);
        }

        return [
            'ai_email_extraction_id' => $aiExtraction?->id,
            'form_autofill_run_id' => $formRun?->id,
            'email_message_id' => $formRun?->email_message_id ?? $aiExtraction?->email_message_id,
            'form_values' => $formRun instanceof FormAutofillRun ? $this->valuesFromFormRun($formRun) : [],
            'ai_values' => $aiExtraction instanceof AiEmailExtraction ? $this->valuesFromAiExtraction($aiExtraction) : [],
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $source
     * @return array<string, mixed>
     */
    private function confirmationData(array $input, array $source): array
    {
        $manualData = is_array($input['manual_confirmation_data'] ?? null) ? $input['manual_confirmation_data'] : [];

        return array_replace_recursive($source['ai_values'], $source['form_values'], $manualData);
    }

    /**
     * @return array<string, mixed>
     */
    private function valuesFromFormRun(FormAutofillRun $run): array
    {
        $values = $run->fieldValues
            ->mapWithKeys(fn ($field): array => [$field->field_key => $field->final_value])
            ->all();

        if (! isset($values['items']) && isset($values['sku'], $values['confirmed_quantity'])) {
            $values['items'] = [[
                'sku' => $values['sku'],
                'confirmed_quantity' => $values['confirmed_quantity'],
                'notes' => $values['notes'] ?? null,
            ]];
        }

        return $values;
    }

    /**
     * @return array<string, mixed>
     */
    private function valuesFromAiExtraction(AiEmailExtraction $extraction): array
    {
        $output = is_array($extraction->output_json) ? $extraction->output_json : [];
        $dates = is_array($output['dates'] ?? null) ? $output['dates'] : [];

        return [
            'supplier_reference' => $output['supplier_reference'] ?? null,
            'supplier_order_number' => $output['supplier_order_number'] ?? null,
            'confirmation_date' => $dates['confirmation_date'] ?? null,
            'ready_date' => $dates['ready_date'] ?? null,
            'shipping_date' => $dates['shipping_date'] ?? null,
            'expected_arrival_date' => $dates['expected_arrival_date'] ?? null,
            'items' => is_array($output['confirmed_items'] ?? null) ? $output['confirmed_items'] : [],
        ];
    }

    /**
     * @param  array<string, mixed>  $confirmationData
     * @return array{confirmation_date:?string,ready_date:?string,shipping_date:?string,expected_arrival_date:?string,discrepancies:list<array<string, mixed>>}
     */
    private function dates(array $confirmationData): array
    {
        $discrepancies = [];
        $dates = [];

        foreach (['confirmation_date', 'ready_date', 'shipping_date', 'expected_arrival_date'] as $key) {
            $normalized = $this->normalizeDate($confirmationData[$key] ?? null);
            $dates[$key] = $normalized['date'];

            if ($normalized['ambiguous']) {
                $discrepancies[] = [
                    'type' => 'ambiguous_date',
                    'field' => $key,
                    'value' => $confirmationData[$key] ?? null,
                ];
            }
        }

        if (($confirmationData['ready_date'] ?? null) === null || ($confirmationData['expected_arrival_date'] ?? null) === null) {
            $discrepancies[] = [
                'type' => 'date_missing',
                'field' => ($confirmationData['ready_date'] ?? null) === null ? 'ready_date' : 'expected_arrival_date',
            ];
        }

        return $dates + ['discrepancies' => $discrepancies];
    }

    /**
     * @return array{date:?string,ambiguous:bool}
     */
    private function normalizeDate(mixed $value): array
    {
        if ($value === null || $value === '') {
            return ['date' => null, 'ambiguous' => false];
        }

        if (is_array($value) || str_contains((string) $value, '?')) {
            return ['date' => null, 'ambiguous' => true];
        }

        try {
            return ['date' => Carbon::parse((string) $value)->toDateString(), 'ambiguous' => false];
        } catch (Throwable) {
            return ['date' => null, 'ambiguous' => true];
        }
    }

    /**
     * @param  array<string, mixed>  $confirmationData
     * @param  list<array<string, mixed>>  $discrepancies
     * @return array{items:list<array{order_item:SupplierOrderItem,confirmed_quantity:float,status:string,notes:?string}>,missing_items:Collection<int, SupplierOrderItem>}
     */
    private function matchedConfirmationItems(SupplierOrder $supplierOrder, array $confirmationData, array &$discrepancies): array
    {
        $items = is_array($confirmationData['items'] ?? null) ? $confirmationData['items'] : [];

        if ($items === [] && isset($confirmationData['sku'], $confirmationData['confirmed_quantity'])) {
            $items = [[
                'sku' => $confirmationData['sku'],
                'confirmed_quantity' => $confirmationData['confirmed_quantity'],
                'notes' => $confirmationData['notes'] ?? null,
            ]];
        }

        $indexes = $this->itemIndexes($supplierOrder);
        $matchedProductIds = [];
        $matched = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $orderItem = $this->matchOrderItem($item, $indexes);

            if (! $orderItem instanceof SupplierOrderItem) {
                $discrepancies[] = [
                    'type' => 'unknown_sku',
                    'sku' => $item['sku'] ?? $item['manufacturer_sku'] ?? $item['supplier_sku'] ?? null,
                    'fuzzy_candidate_product_id' => $this->fuzzyCandidateProductId($supplierOrder, $item),
                ];
                $discrepancies[] = [
                    'type' => 'additional_item',
                    'item' => $item,
                ];

                continue;
            }

            $confirmedQuantity = (float) ($item['confirmed_quantity'] ?? $item['quantity'] ?? 0);
            $orderedQuantity = (float) $orderItem->ordered_quantity;
            $status = 'confirmed';

            if ($confirmedQuantity < $orderedQuantity) {
                $status = 'quantity_lower_than_ordered';
                $discrepancies[] = [
                    'type' => 'quantity_lower_than_ordered',
                    'product_id' => $orderItem->product_id,
                    'ordered_quantity' => $orderedQuantity,
                    'confirmed_quantity' => $confirmedQuantity,
                ];
            }

            if ($confirmedQuantity > $orderedQuantity) {
                $status = 'quantity_higher_than_ordered';
                $discrepancies[] = [
                    'type' => 'quantity_higher_than_ordered',
                    'product_id' => $orderItem->product_id,
                    'ordered_quantity' => $orderedQuantity,
                    'confirmed_quantity' => $confirmedQuantity,
                ];
            }

            $matched[] = [
                'order_item' => $orderItem,
                'confirmed_quantity' => $confirmedQuantity,
                'status' => $status,
                'notes' => isset($item['notes']) ? (string) $item['notes'] : null,
            ];
            $matchedProductIds[] = $orderItem->product_id;
        }

        $missingItems = $supplierOrder->items
            ->reject(fn (SupplierOrderItem $item): bool => in_array($item->product_id, $matchedProductIds, true))
            ->values();

        foreach ($missingItems as $missingItem) {
            $discrepancies[] = [
                'type' => 'missing_item',
                'product_id' => $missingItem->product_id,
                'ordered_quantity' => (float) $missingItem->ordered_quantity,
            ];
        }

        return [
            'items' => $matched,
            'missing_items' => $missingItems,
        ];
    }

    /**
     * @return array<string, SupplierOrderItem>
     */
    private function itemIndexes(SupplierOrder $supplierOrder): array
    {
        $indexes = [];

        foreach ($supplierOrder->items as $orderItem) {
            $product = $orderItem->product;

            foreach ([$product?->sku, $product?->manufacturer_sku] as $value) {
                $key = $this->skuKey($value);

                if ($key !== null) {
                    $indexes[$key] = $orderItem;
                }
            }

            foreach ($product?->supplierProductRules ?? [] as $rule) {
                if ((int) $rule->supplier_id !== (int) $supplierOrder->supplier_id) {
                    continue;
                }

                $key = $this->skuKey($rule->supplier_sku);

                if ($key !== null) {
                    $indexes[$key] = $orderItem;
                }
            }
        }

        return $indexes;
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  array<string, SupplierOrderItem>  $indexes
     */
    private function matchOrderItem(array $item, array $indexes): ?SupplierOrderItem
    {
        foreach (['sku', 'manufacturer_sku', 'supplier_sku'] as $key) {
            $skuKey = $this->skuKey($item[$key] ?? null);

            if ($skuKey !== null && isset($indexes[$skuKey])) {
                return $indexes[$skuKey];
            }
        }

        return null;
    }

    private function skuKey(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : mb_strtoupper($value);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function fuzzyCandidateProductId(SupplierOrder $supplierOrder, array $item): ?int
    {
        $needle = trim((string) ($item['name'] ?? $item['product_name'] ?? ''));

        if ($needle === '') {
            return null;
        }

        $bestScore = 0.0;
        $candidateId = null;

        foreach ($supplierOrder->items as $orderItem) {
            similar_text(mb_strtoupper($needle), mb_strtoupper((string) $orderItem->product?->name), $score);

            if ($score > $bestScore) {
                $bestScore = $score;
                $candidateId = $orderItem->product_id;
            }
        }

        return $bestScore >= 70.0 ? $candidateId : null;
    }

    /**
     * @param  array{items:list<array{order_item:SupplierOrderItem,confirmed_quantity:float,status:string,notes:?string}>,missing_items:Collection<int, SupplierOrderItem>}  $matched
     * @param  list<array<string, mixed>>  $discrepancies
     */
    private function statusFor(array $matched, array $discrepancies): SupplierOrderStatus
    {
        if ($this->hasDiscrepancy($discrepancies, [
            'unknown_sku',
            'date_missing',
            'date_changed',
            'ambiguous_date',
            'delayed_ready_date',
            'delayed_arrival_date',
        ])) {
            return SupplierOrderStatus::NeedsReview;
        }

        if ($matched['missing_items']->isNotEmpty() || $this->hasDiscrepancy($discrepancies, [
            'quantity_lower_than_ordered',
            'quantity_higher_than_ordered',
            'additional_item',
        ])) {
            return SupplierOrderStatus::PartiallyConfirmed;
        }

        return SupplierOrderStatus::Confirmed;
    }

    private function confirmationStatusFor(SupplierOrderStatus $status): SupplierConfirmationStatus
    {
        return match ($status) {
            SupplierOrderStatus::Confirmed => SupplierConfirmationStatus::Confirmed,
            SupplierOrderStatus::PartiallyConfirmed => SupplierConfirmationStatus::PartiallyConfirmed,
            default => SupplierConfirmationStatus::NeedsReview,
        };
    }

    /**
     * @param  list<array<string, mixed>>  $discrepancies
     * @param  list<string>  $types
     */
    private function hasDiscrepancy(array $discrepancies, array $types): bool
    {
        foreach ($discrepancies as $discrepancy) {
            if (in_array((string) ($discrepancy['type'] ?? ''), $types, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array{items:list<array{order_item:SupplierOrderItem,confirmed_quantity:float,status:string,notes:?string}>,missing_items:Collection<int, SupplierOrderItem>}  $matched
     * @param  array<string, ?string>  $dates
     * @return Collection<int, InboundOrder>
     */
    private function updateInboundOrders(SupplierOrder $supplierOrder, array $matched, array $dates, SupplierOrderStatus $status): Collection
    {
        $inboundOrders = InboundOrder::query()
            ->with('items')
            ->where('company_id', $supplierOrder->company_id)
            ->where('supplier_id', $supplierOrder->supplier_id)
            ->where(function ($query) use ($supplierOrder): void {
                $query
                    ->where('order_number', $supplierOrder->order_number)
                    ->orWhere('supplier_order_reference', $supplierOrder->order_number);
            })
            ->get();

        foreach ($inboundOrders as $inboundOrder) {
            $inboundOrder->forceFill([
                'status' => $status->value,
                'confirmed_arrival_date' => $dates['expected_arrival_date'] ?? $inboundOrder->confirmed_arrival_date,
                'ready_date' => $dates['ready_date'] ?? $inboundOrder->ready_date,
                'shipped_date' => $dates['shipping_date'] ?? $inboundOrder->shipped_date,
            ])->save();

            foreach ($matched['items'] as $matchedItem) {
                $inboundItem = $inboundOrder->items->firstWhere('product_id', $matchedItem['order_item']->product_id);

                if ($inboundItem === null) {
                    continue;
                }

                $inboundItem->forceFill([
                    'confirmed_quantity' => $matchedItem['confirmed_quantity'],
                    'confirmed_arrival_date' => $dates['expected_arrival_date'] ?? $inboundItem->confirmed_arrival_date,
                    'status' => $matchedItem['status'],
                ])->save();
            }
        }

        return $inboundOrders;
    }

    /**
     * @param  array<string, ?string>  $dates
     */
    private function updateLogisticsRecord(SupplierOrder $supplierOrder, array $dates, SupplierOrderStatus $status): LogisticsRecord
    {
        $record = LogisticsRecord::query()->firstOrCreate([
            'company_id' => $supplierOrder->company_id,
            'supplier_order_id' => $supplierOrder->id,
        ], [
            'supplier_id' => $supplierOrder->supplier_id,
            'order_date' => $supplierOrder->order_date,
            'status' => LogisticsStatus::Planned,
        ]);

        $record->forceFill([
            'confirmation_date' => $dates['confirmation_date'] ?? $record->confirmation_date,
            'ready_date' => $dates['ready_date'] ?? $record->ready_date,
            'pickup_date' => $dates['shipping_date'] ?? $record->pickup_date,
            'delivery_date' => $dates['expected_arrival_date'] ?? $record->delivery_date,
            'status' => $status === SupplierOrderStatus::NeedsReview ? LogisticsStatus::NeedsReview : LogisticsStatus::Confirmed,
        ])->save();

        return $record;
    }

    /**
     * @param  array<string, ?string>  $dates
     * @param  list<array<string, mixed>>  $discrepancies
     * @param  list<string>  $riskReasons
     */
    private function collectLogisticsDateDiscrepancies(SupplierOrder $supplierOrder, array $dates, array &$discrepancies, array &$riskReasons): void
    {
        $record = $supplierOrder->logisticsRecords->sortByDesc('id')->first();

        if (! $record instanceof LogisticsRecord) {
            return;
        }

        $this->compareLogisticsDate($record->ready_date?->toDateString(), $dates['ready_date'] ?? null, 'ready_date', 'delayed_ready_date', $discrepancies, $riskReasons);
        $this->compareLogisticsDate($record->delivery_date?->toDateString(), $dates['expected_arrival_date'] ?? null, 'expected_arrival_date', 'delayed_arrival_date', $discrepancies, $riskReasons);
    }

    /**
     * @param  list<array<string, mixed>>  $discrepancies
     * @param  list<string>  $riskReasons
     */
    private function compareLogisticsDate(
        ?string $oldDate,
        ?string $newDate,
        string $field,
        string $delayType,
        array &$discrepancies,
        array &$riskReasons,
    ): void {
        if ($oldDate === null || $newDate === null || $oldDate === $newDate) {
            return;
        }

        $discrepancies[] = [
            'type' => 'date_changed',
            'field' => $field,
            'old_date' => $oldDate,
            'new_date' => $newDate,
        ];
        $riskReasons[] = 'date_changed';

        if (Carbon::parse($newDate)->gt(Carbon::parse($oldDate))) {
            $discrepancies[] = [
                'type' => $delayType,
                'field' => $field,
                'old_date' => $oldDate,
                'new_date' => $newDate,
            ];
            $riskReasons[] = $delayType;
        }
    }

    /**
     * @param  list<array<string, mixed>>  $discrepancies
     * @return list<string>
     */
    private function riskReasonsFromDiscrepancies(array $discrepancies): array
    {
        $riskTypes = [
            'quantity_lower_than_ordered',
            'quantity_higher_than_ordered',
            'missing_item',
            'date_changed',
            'delayed_ready_date',
            'delayed_arrival_date',
        ];

        return collect($discrepancies)
            ->pluck('type')
            ->filter(fn (mixed $type): bool => is_string($type) && in_array($type, $riskTypes, true))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    private function writeAuditLog(
        SupplierOrder $supplierOrder,
        SupplierConfirmation $confirmation,
        int $userId,
        array $oldValues,
        array $newValues,
    ): void {
        AuditLog::query()->create([
            'company_id' => $supplierOrder->company_id,
            'user_id' => $userId > 0 ? $userId : null,
            'event_type' => 'supplier_confirmation.applied',
            'auditable_type' => $confirmation::class,
            'auditable_id' => $confirmation->id,
            'old_values_json' => $oldValues,
            'new_values_json' => $newValues,
            'metadata_json' => [
                'supplier_order_id' => $supplierOrder->id,
            ],
            'created_at' => now(),
        ]);
    }
}
