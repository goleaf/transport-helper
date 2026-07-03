<?php

namespace App\Services\AI;

use App\Enums\SupplierConfirmationStatus;
use App\Models\AiEmailExtraction;
use App\Models\SupplierConfirmation;
use App\Models\SupplierOrder;
use Illuminate\Validation\ValidationException;

class SupplierConfirmationFromAiExtractionService
{
    public function create(AiEmailExtraction $extraction): SupplierConfirmation
    {
        $extraction->loadMissing('emailMessage.relatedSupplierOrder');
        $output = is_array($extraction->output_json) ? $extraction->output_json : [];
        $supplierOrder = $this->supplierOrderFor($extraction, $output);

        if (! $supplierOrder instanceof SupplierOrder) {
            throw ValidationException::withMessages([
                'supplier_order' => 'Supplier confirmation requires a linked supplier order.',
            ]);
        }

        $dates = is_array($output['dates'] ?? null) ? $output['dates'] : [];
        $confirmedItems = is_array($output['confirmed_items'] ?? null) ? $output['confirmed_items'] : [];
        $hasDiscrepancy = ($output['discrepancies'] ?? []) !== [];

        $confirmation = SupplierConfirmation::query()->create([
            'company_id' => $supplierOrder->company_id,
            'supplier_order_id' => $supplierOrder->id,
            'email_message_id' => $extraction->email_message_id,
            'supplier_reference' => $output['supplier_reference'] ?? null,
            'confirmation_date' => $dates['confirmation_date'] ?? now()->toDateString(),
            'ready_date' => $dates['ready_date'] ?? null,
            'shipping_date' => $dates['shipping_date'] ?? null,
            'expected_arrival_date' => $dates['expected_arrival_date'] ?? null,
            'status' => $hasDiscrepancy ? SupplierConfirmationStatus::NeedsReview : SupplierConfirmationStatus::Confirmed,
            'discrepancy_summary' => $hasDiscrepancy ? json_encode($output['discrepancies']) : null,
            'created_from_ai_extraction_id' => $extraction->id,
        ]);

        $supplierOrder->loadMissing('items.product:id,sku,name');
        $itemsBySku = $supplierOrder->items->keyBy(fn ($item): string => (string) $item->product?->sku);

        foreach ($confirmedItems as $confirmedItem) {
            if (! is_array($confirmedItem)) {
                continue;
            }

            $sku = (string) ($confirmedItem['sku'] ?? '');
            $orderItem = $itemsBySku->get($sku);

            if ($orderItem === null) {
                continue;
            }

            $confirmedQuantity = (float) ($confirmedItem['confirmed_quantity'] ?? $confirmedItem['quantity'] ?? $orderItem->ordered_quantity);
            $orderedQuantity = (float) $orderItem->ordered_quantity;

            $confirmation->items()->create([
                'product_id' => $orderItem->product_id,
                'ordered_quantity' => $orderItem->ordered_quantity,
                'confirmed_quantity' => $confirmedQuantity,
                'discrepancy_quantity' => $confirmedQuantity - $orderedQuantity,
                'status' => abs($confirmedQuantity - $orderedQuantity) > 0.0001 ? 'quantity_mismatch' : 'confirmed',
                'notes' => $confirmedItem['notes'] ?? null,
            ]);
        }

        return $confirmation->load('items');
    }

    private function supplierOrderFor(AiEmailExtraction $extraction, array $output): ?SupplierOrder
    {
        if ($extraction->emailMessage?->relatedSupplierOrder instanceof SupplierOrder) {
            return $extraction->emailMessage->relatedSupplierOrder;
        }

        $orderNumber = $output['supplier_order_number'] ?? null;

        if (! is_string($orderNumber) || $orderNumber === '') {
            return null;
        }

        return SupplierOrder::query()
            ->where('company_id', $extraction->emailMessage?->company_id)
            ->where('order_number', $orderNumber)
            ->first();
    }
}
