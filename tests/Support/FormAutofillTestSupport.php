<?php

namespace Tests\Support;

use App\Enums\EmailDirection;
use App\Enums\FormTemplateContextType;
use App\Enums\FormTemplateFormatType;
use App\Enums\SupplierOrderStatus;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\EmailAccount;
use App\Models\EmailMessage;
use App\Models\FormTemplate;
use App\Models\FormTemplateField;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierOrder;
use App\Models\SupplierOrderItem;
use App\Models\User;

class FormAutofillTestSupport
{
    /**
     * @return array<string, mixed>
     */
    public static function fixture(string $contextType = 'supplier_confirmation'): array
    {
        $company = Company::factory()->create(['name' => 'Demo Supply Co']);
        $supplier = Supplier::factory()->for($company)->create(['name' => 'Acme Manufacturing']);
        $product = Product::factory()->for($company)->create([
            'sku' => 'AX-150',
            'manufacturer_sku' => 'MFG-AX-150',
            'name' => 'Axle Bearing 150',
        ]);
        $supplierOrder = SupplierOrder::factory()->for($company)->for($supplier)->create([
            'order_number' => 'PO-AUTOFILL-1',
            'status' => SupplierOrderStatus::Sent,
        ]);
        SupplierOrderItem::factory()->create([
            'supplier_order_id' => $supplierOrder->id,
            'product_id' => $product->id,
            'ordered_quantity' => 156,
        ]);
        $emailAccount = EmailAccount::factory()->for($company)->create(['provider' => 'manual']);
        $email = EmailMessage::factory()->for($company)->for($emailAccount)->create([
            'direction' => EmailDirection::Inbound,
            'from_email' => 'orders@acme.test',
            'subject' => 'Confirmation for PO-AUTOFILL-1',
            'body_text' => 'Confirmation no. CONF-88421. AX-150 confirmed 156 pcs. Ready 2026-08-14. Shipping 2026-08-15.',
            'related_supplier_id' => $supplier->id,
            'related_supplier_order_id' => $supplierOrder->id,
            'status' => 'linked',
        ]);
        $template = self::template($company, $contextType);
        $user = User::factory()->create(['role' => UserRole::Admin]);

        return compact('company', 'supplier', 'product', 'supplierOrder', 'emailAccount', 'email', 'template', 'user');
    }

    public static function template(Company $company, string $contextType = 'supplier_confirmation'): FormTemplate
    {
        $template = FormTemplate::factory()->for($company)->create([
            'supplier_id' => null,
            'carrier_id' => null,
            'name' => 'Supplier Confirmation Form',
            'code' => $contextType.'_v1',
            'context_type' => $contextType,
            'format_type' => FormTemplateFormatType::InternalHtml,
            'version' => '1',
            'is_active' => true,
        ]);

        $fields = $contextType === FormTemplateContextType::CarrierQuote->value
            ? [
                ['carrier_name', 'Carrier name', 'text', true],
                ['price', 'Price', 'decimal', true],
                ['currency', 'Currency', 'currency', true],
                ['delivery_date', 'Delivery date', 'date', true],
            ]
            : [
                ['supplier_reference', 'Supplier reference', 'text', false],
                ['supplier_order_number', 'Supplier order number', 'text', true],
                ['sku', 'SKU', 'sku', true],
                ['confirmed_quantity', 'Confirmed quantity', 'decimal', true],
                ['ready_date', 'Ready date', 'date', false],
                ['shipping_date', 'Shipping date', 'date', false],
                ['notes', 'Notes', 'textarea', false],
            ];

        foreach ($fields as $index => [$key, $label, $type, $required]) {
            FormTemplateField::factory()->for($template)->create([
                'field_key' => $key,
                'label' => $label,
                'field_type' => $type,
                'is_required' => $required,
                'validation_rules_json' => $required ? ['required'] : ['nullable'],
                'sort_order' => ($index + 1) * 10,
            ]);
        }

        return $template->refresh();
    }

    /**
     * @return array<string, mixed>
     */
    public static function aiOutput(array $overrides = []): array
    {
        return array_replace_recursive([
            'form_type' => 'supplier_confirmation',
            'overall_confidence' => 0.96,
            'fields' => [
                'supplier_reference' => ['value' => 'CONF-88421', 'confidence' => 0.94, 'source_excerpt' => 'Confirmation no. CONF-88421'],
                'supplier_order_number' => ['value' => 'PO-AUTOFILL-1', 'confidence' => 0.97, 'source_excerpt' => 'PO-AUTOFILL-1'],
                'sku' => ['value' => 'AX-150', 'confidence' => 0.97, 'source_excerpt' => 'AX-150 confirmed'],
                'confirmed_quantity' => ['value' => '156 pcs', 'confidence' => 0.96, 'source_excerpt' => '156 pcs confirmed'],
                'ready_date' => ['value' => '2026-08-14', 'confidence' => 0.94, 'source_excerpt' => 'Ready 2026-08-14'],
                'shipping_date' => ['value' => '2026-08-15', 'confidence' => 0.94, 'source_excerpt' => 'Shipping 2026-08-15'],
                'notes' => ['value' => 'Supplier confirmed order.', 'confidence' => 0.88, 'source_excerpt' => 'confirmed order'],
            ],
            'warnings' => [],
            'requires_human_review' => false,
            'human_review_reason' => null,
        ], $overrides);
    }
}
