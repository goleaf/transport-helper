<?php

namespace Tests\Support;

use App\Enums\EmailDirection;
use App\Enums\FormAutofillRunStatus;
use App\Enums\FormTemplateFormatType;
use App\Enums\LogisticsStatus;
use App\Enums\SupplierOrderStatus;
use App\Enums\UserRole;
use App\Models\AiEmailExtraction;
use App\Models\Company;
use App\Models\EmailMessage;
use App\Models\FormAutofillFieldValue;
use App\Models\FormAutofillRun;
use App\Models\FormTemplate;
use App\Models\FormTemplateField;
use App\Models\InboundOrder;
use App\Models\InboundOrderItem;
use App\Models\LogisticsRecord;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierOrder;
use App\Models\SupplierOrderItem;
use App\Models\SupplierProductRule;
use App\Models\User;

class SupplierConfirmationTestSupport
{
    /**
     * @return array<string, mixed>
     */
    public static function fixture(bool $withSecondItem = false): array
    {
        $company = Company::factory()->create(['name' => 'Confirmation Demo Co']);
        $supplier = Supplier::factory()->for($company)->create([
            'name' => 'Acme Manufacturing',
            'type' => 'manufacturer',
        ]);
        $product = Product::factory()->for($company)->create([
            'sku' => 'AX-150',
            'manufacturer_sku' => 'MFG-AX-150',
            'name' => 'Axle Bearing 150',
        ]);
        $secondProduct = Product::factory()->for($company)->create([
            'sku' => 'BRK-200',
            'manufacturer_sku' => 'MFG-BRK-200',
            'name' => 'Brake Kit 200',
        ]);

        SupplierProductRule::factory()->create([
            'supplier_id' => $supplier->getKey(),
            'product_id' => $product->getKey(),
            'supplier_sku' => 'SUP-AX-150',
        ]);
        SupplierProductRule::factory()->create([
            'supplier_id' => $supplier->getKey(),
            'product_id' => $secondProduct->getKey(),
            'supplier_sku' => 'SUP-BRK-200',
        ]);

        $supplierOrder = SupplierOrder::factory()->create([
            'company_id' => $company->getKey(),
            'supplier_id' => $supplier->getKey(),
            'order_number' => 'PO-CONF-1',
            'status' => SupplierOrderStatus::Sent,
            'order_date' => '2026-07-03',
        ]);
        $supplierOrderItem = SupplierOrderItem::factory()->create([
            'supplier_order_id' => $supplierOrder->getKey(),
            'product_id' => $product->getKey(),
            'ordered_quantity' => 156,
            'confirmed_quantity' => null,
            'received_quantity' => null,
        ]);
        $secondSupplierOrderItem = null;

        if ($withSecondItem) {
            $secondSupplierOrderItem = SupplierOrderItem::factory()->create([
                'supplier_order_id' => $supplierOrder->getKey(),
                'product_id' => $secondProduct->getKey(),
                'ordered_quantity' => 24,
                'confirmed_quantity' => null,
                'received_quantity' => null,
            ]);
        }

        $inboundOrder = InboundOrder::factory()->create([
            'company_id' => $company->getKey(),
            'supplier_id' => $supplier->getKey(),
            'supplier_order_id' => $supplierOrder->getKey(),
            'order_number' => 'PO-CONF-1',
            'status' => 'open',
            'expected_arrival_date' => '2026-07-20',
        ]);
        $inboundOrderItem = InboundOrderItem::factory()->create([
            'inbound_order_id' => $inboundOrder->getKey(),
            'product_id' => $product->getKey(),
            'ordered_quantity' => 156,
            'confirmed_quantity' => null,
            'received_quantity' => null,
            'expected_arrival_date' => '2026-07-20',
        ]);
        $logisticsRecord = LogisticsRecord::factory()->create([
            'company_id' => $company->getKey(),
            'supplier_order_id' => $supplierOrder->getKey(),
            'supplier_id' => $supplier->getKey(),
            'order_date' => '2026-07-03',
            'ready_date' => '2026-07-10',
            'delivery_date' => '2026-07-20',
            'status' => LogisticsStatus::Planned,
            'transport_price' => null,
            'carrier_id' => null,
        ]);
        $email = EmailMessage::factory()->create([
            'company_id' => $company->getKey(),
            'direction' => EmailDirection::Inbound,
            'from_email' => 'orders@acme.test',
            'subject' => 'Confirmation PO-CONF-1',
            'body_text' => 'SKU AX-150 confirmed 156 pcs.',
            'related_supplier_id' => $supplier->getKey(),
            'related_supplier_order_id' => $supplierOrder->getKey(),
            'status' => 'linked',
        ]);
        $user = User::factory()->create(['role' => UserRole::Admin]);

        return compact(
            'company',
            'supplier',
            'product',
            'secondProduct',
            'supplierOrder',
            'supplierOrderItem',
            'secondSupplierOrderItem',
            'inboundOrder',
            'inboundOrderItem',
            'logisticsRecord',
            'email',
            'user',
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function manualData(array $overrides = []): array
    {
        $data = [
            'supplier_reference' => 'CONF-9001',
            'confirmation_date' => '2026-07-03',
            'ready_date' => '2026-07-10',
            'shipping_date' => '2026-07-11',
            'expected_arrival_date' => '2026-07-20',
            'items' => [
                [
                    'supplier_sku' => 'SUP-AX-150',
                    'confirmed_quantity' => 156,
                    'unit' => 'pcs',
                    'notes' => 'Confirmed by supplier.',
                ],
            ],
        ];

        if (array_key_exists('items', $overrides)) {
            $data['items'] = $overrides['items'];
            unset($overrides['items']);
        }

        return array_replace_recursive($data, $overrides);
    }

    public static function acceptedAiExtraction(array $fixture, array $output = []): AiEmailExtraction
    {
        return AiEmailExtraction::factory()->create([
            'email_message_id' => $fixture['email']->getKey(),
            'output_json' => array_replace_recursive([
                'email_type' => 'supplier_confirmation',
                'supplier_order_number' => 'PO-CONF-1',
                'supplier_reference' => 'AI-CONF-1',
                'confirmed_items' => [
                    [
                        'sku' => 'AX-150',
                        'confirmed_quantity' => 156,
                        'source_excerpt' => 'AX-150 confirmed 156 pcs',
                    ],
                ],
                'dates' => [
                    'confirmation_date' => '2026-07-03',
                    'ready_date' => '2026-07-10',
                    'shipping_date' => '2026-07-11',
                    'expected_arrival_date' => '2026-07-20',
                ],
            ], $output),
            'accepted_at' => now(),
            'rejected_at' => null,
        ]);
    }

    public static function validatedFormRun(array $fixture, string $contextType = 'supplier_confirmation'): FormAutofillRun
    {
        $template = FormTemplate::factory()->create([
            'company_id' => $fixture['company']->getKey(),
            'supplier_id' => $fixture['supplier']->getKey(),
            'context_type' => $contextType,
            'format_type' => FormTemplateFormatType::InternalHtml,
            'is_active' => true,
        ]);

        foreach ([
            ['supplier_order_number', 'Supplier order number', 'text', true],
            ['sku', 'SKU', 'sku', true],
            ['confirmed_quantity', 'Confirmed quantity', 'decimal', true],
            ['supplier_reference', 'Supplier reference', 'text', false],
            ['ready_date', 'Ready date', 'date', false],
            ['shipping_date', 'Shipping date', 'date', false],
            ['expected_arrival_date', 'Expected arrival date', 'date', false],
        ] as $index => [$key, $label, $type, $required]) {
            FormTemplateField::factory()->create([
                'form_template_id' => $template->getKey(),
                'field_key' => $key,
                'label' => $label,
                'field_type' => $type,
                'is_required' => $required,
                'sort_order' => ($index + 1) * 10,
            ]);
        }

        $run = FormAutofillRun::factory()->create([
            'company_id' => $fixture['company']->getKey(),
            'email_message_id' => $fixture['email']->getKey(),
            'form_template_id' => $template->getKey(),
            'status' => FormAutofillRunStatus::Validated,
            'created_by_user_id' => $fixture['user']->getKey(),
            'reviewed_by_user_id' => $fixture['user']->getKey(),
        ]);

        foreach ([
            'supplier_order_number' => 'PO-CONF-1',
            'sku' => 'AX-150',
            'confirmed_quantity' => 156,
            'supplier_reference' => 'FORM-CONF-1',
            'ready_date' => '2026-07-10',
            'shipping_date' => '2026-07-11',
            'expected_arrival_date' => '2026-07-20',
        ] as $fieldKey => $value) {
            FormAutofillFieldValue::factory()->create([
                'form_autofill_run_id' => $run->getKey(),
                'field_key' => $fieldKey,
                'extracted_value' => $value,
                'normalized_value' => $value,
                'final_value' => $value,
                'source_excerpt' => $fieldKey.' source excerpt',
                'requires_review' => false,
                'accepted_by_user_id' => $fixture['user']->getKey(),
                'accepted_at' => now(),
            ]);
        }

        return $run->refresh();
    }
}
