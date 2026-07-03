<?php

namespace App\Services\Forms;

use App\Models\Carrier;
use App\Models\EmailMessage;
use App\Models\FormTemplate;
use App\Models\SupplierOrder;
use App\Models\SupplierProductRule;
use Illuminate\Support\Facades\Schema;

class FormAutofillContextBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function build(EmailMessage $email, FormTemplate $template): array
    {
        $email->loadMissing([
            'attachments:id,email_message_id,original_filename,mime_type,size_bytes',
            'relatedSupplier:id,name,code,default_currency',
            'relatedSupplierOrder.items.product:id,sku,manufacturer_sku,name',
        ]);
        $template->loadMissing('fields');

        $supplierOrder = $email->relatedSupplierOrder;
        $warnings = [];
        $expectedItems = $this->expectedItems($supplierOrder);

        if ($email->relatedSupplier === null) {
            $warnings[] = 'no_related_supplier';
        }

        if (! $supplierOrder instanceof SupplierOrder) {
            $warnings[] = 'no_related_supplier_order';
        }

        if ($expectedItems === []) {
            $warnings[] = 'no_expected_items';
        }

        if ($template->fields->isEmpty()) {
            $warnings[] = 'no_template_fields';
        }

        return [
            'supplier' => $email->relatedSupplier ? [
                'id' => $email->relatedSupplier->id,
                'name' => $email->relatedSupplier->name,
                'code' => $email->relatedSupplier->code,
                'default_currency' => $email->relatedSupplier->default_currency,
            ] : null,
            'supplier_order_model' => $supplierOrder,
            'supplier_order' => $supplierOrder instanceof SupplierOrder ? [
                'id' => $supplierOrder->id,
                'order_number' => $supplierOrder->order_number,
                'status' => $supplierOrder->status instanceof \BackedEnum ? $supplierOrder->status->value : $supplierOrder->status,
            ] : null,
            'expected_items' => $expectedItems,
            'known_products' => $this->knownProducts($email, $supplierOrder),
            'known_carriers' => $this->knownCarriers($email->company_id),
            'attachments_summary' => $email->attachments->map(fn ($attachment): array => [
                'filename' => $attachment->original_filename,
                'mime_type' => $attachment->mime_type,
                'size_bytes' => $attachment->size_bytes,
            ])->values()->all(),
            'warnings' => $warnings,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function expectedItems(?SupplierOrder $supplierOrder): array
    {
        if (! $supplierOrder instanceof SupplierOrder) {
            return [];
        }

        return $supplierOrder->items->map(fn ($item): array => [
            'product_id' => $item->product_id,
            'sku' => $item->product?->sku,
            'manufacturer_sku' => $item->product?->manufacturer_sku,
            'supplier_sku' => null,
            'ordered_quantity' => (float) $item->ordered_quantity,
            'unit' => $item->unit ?? null,
        ])->values()->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function knownProducts(EmailMessage $email, ?SupplierOrder $supplierOrder): array
    {
        if ($supplierOrder instanceof SupplierOrder) {
            return $supplierOrder->items->map(fn ($item): array => [
                'product_id' => $item->product_id,
                'sku' => $item->product?->sku,
                'manufacturer_sku' => $item->product?->manufacturer_sku,
                'name' => $item->product?->name,
            ])->values()->all();
        }

        if ($email->related_supplier_id === null) {
            return [];
        }

        return SupplierProductRule::query()
            ->select(['id', 'supplier_id', 'product_id', 'supplier_sku'])
            ->with('product:id,sku,manufacturer_sku,name')
            ->where('supplier_id', $email->related_supplier_id)
            ->limit(100)
            ->get()
            ->map(fn (SupplierProductRule $rule): array => [
                'product_id' => $rule->product_id,
                'sku' => $rule->product?->sku,
                'manufacturer_sku' => $rule->product?->manufacturer_sku,
                'supplier_sku' => $rule->supplier_sku,
                'name' => $rule->product?->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function knownCarriers(int $companyId): array
    {
        if (! Schema::hasTable('carriers')) {
            return [];
        }

        return Carrier::query()
            ->select(['id', 'name', 'code', 'default_currency'])
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(100)
            ->get()
            ->map(fn (Carrier $carrier): array => [
                'id' => $carrier->id,
                'name' => $carrier->name,
                'code' => $carrier->code,
                'default_currency' => $carrier->default_currency,
            ])
            ->all();
    }
}
