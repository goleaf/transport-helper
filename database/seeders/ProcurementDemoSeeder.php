<?php

namespace Database\Seeders;

use App\Models\Carrier;
use App\Models\CarrierContact;
use App\Models\Company;
use App\Models\FormTemplate;
use App\Models\FormTemplateField;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierContact;
use App\Models\SupplierProductRule;
use Illuminate\Database\Seeder;

class ProcurementDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company = Company::query()->updateOrCreate(
            ['code' => 'DEMO'],
            [
                'name' => 'Demo Supply Company',
                'timezone' => 'Europe/Vilnius',
                'default_currency' => 'EUR',
            ]
        );

        $manufacturer = Supplier::query()->updateOrCreate(
            [
                'company_id' => $company->getKey(),
                'code' => 'BALTIC-PARTS',
            ],
            [
                'name' => 'Baltic Parts Manufacturing',
                'type' => 'manufacturer',
                'default_language' => 'en',
                'default_currency' => 'EUR',
                'default_lead_time_days' => 21,
                'is_active' => true,
                'notes' => 'Demo manufacturer for supplier order workflows.',
            ]
        );

        $distributor = Supplier::query()->updateOrCreate(
            [
                'company_id' => $company->getKey(),
                'code' => 'NORDIC-DIST',
            ],
            [
                'name' => 'Nordic Distribution',
                'type' => 'distributor',
                'default_language' => 'en',
                'default_currency' => 'EUR',
                'default_lead_time_days' => 14,
                'is_active' => true,
                'notes' => 'Demo distributor for fallback procurement.',
            ]
        );

        SupplierContact::query()->updateOrCreate(
            [
                'supplier_id' => $manufacturer->getKey(),
                'email' => 'orders@baltic-parts.example',
            ],
            [
                'name' => 'Anna Supplier',
                'phone' => '+37060000001',
                'role' => 'Orders',
                'receives_orders' => true,
                'receives_transport_requests' => false,
                'is_active' => true,
            ]
        );

        SupplierContact::query()->updateOrCreate(
            [
                'supplier_id' => $distributor->getKey(),
                'email' => 'sales@nordic-dist.example',
            ],
            [
                'name' => 'Mark Distributor',
                'phone' => '+37060000002',
                'role' => 'Sales',
                'receives_orders' => true,
                'receives_transport_requests' => false,
                'is_active' => true,
            ]
        );

        $carrier = Carrier::query()->updateOrCreate(
            [
                'company_id' => $company->getKey(),
                'code' => 'EXPRESS-ROAD',
            ],
            [
                'name' => 'Express Road Logistics',
                'default_currency' => 'EUR',
                'reliability_score' => 92.50,
                'is_active' => true,
                'notes' => 'Demo road freight carrier.',
            ]
        );

        CarrierContact::query()->updateOrCreate(
            [
                'carrier_id' => $carrier->getKey(),
                'email' => 'quotes@express-road.example',
            ],
            [
                'name' => 'Laura Carrier',
                'phone' => '+37060000003',
                'is_active' => true,
            ]
        );

        $products = [
            [
                'supplier' => $manufacturer,
                'sku' => 'AX-150',
                'manufacturer_sku' => 'BP-AX-150',
                'name' => 'Axle Set',
                'category' => 'Drive Train',
                'brand' => 'Baltic Parts',
                'unit' => 'pcs',
                'supplier_sku' => 'BP-AX-150',
                'moq' => 12,
                'pack_multiple' => 6,
                'pallet_quantity' => 156,
                'lead_time_days' => 21,
                'safety_days' => 7,
            ],
            [
                'supplier' => $manufacturer,
                'sku' => 'BRK-200',
                'manufacturer_sku' => 'BP-BRK-200',
                'name' => 'Brake Kit',
                'category' => 'Brakes',
                'brand' => 'Baltic Parts',
                'unit' => 'pcs',
                'supplier_sku' => 'BP-BRK-200',
                'moq' => 24,
                'pack_multiple' => 12,
                'pallet_quantity' => 240,
                'lead_time_days' => 18,
                'safety_days' => 5,
            ],
            [
                'supplier' => $distributor,
                'sku' => 'FLT-010',
                'manufacturer_sku' => 'ND-FLT-010',
                'name' => 'Filter Cartridge',
                'category' => 'Filters',
                'brand' => 'Nordic Distribution',
                'unit' => 'pcs',
                'supplier_sku' => 'ND-FLT-010',
                'moq' => 50,
                'pack_multiple' => 25,
                'pallet_quantity' => 500,
                'lead_time_days' => 14,
                'safety_days' => 7,
            ],
        ];

        foreach ($products as $productData) {
            $ruleSupplier = $productData['supplier'];
            unset($productData['supplier']);

            $product = Product::query()->updateOrCreate(
                [
                    'company_id' => $company->getKey(),
                    'sku' => $productData['sku'],
                ],
                [
                    'manufacturer_sku' => $productData['manufacturer_sku'],
                    'name' => $productData['name'],
                    'category' => $productData['category'],
                    'brand' => $productData['brand'],
                    'unit' => $productData['unit'],
                    'is_active' => true,
                ]
            );

            SupplierProductRule::query()->updateOrCreate(
                [
                    'supplier_id' => $ruleSupplier->getKey(),
                    'product_id' => $product->getKey(),
                ],
                [
                    'supplier_sku' => $productData['supplier_sku'],
                    'moq' => $productData['moq'],
                    'pack_multiple' => $productData['pack_multiple'],
                    'pallet_quantity' => $productData['pallet_quantity'],
                    'min_transport_quantity' => null,
                    'lead_time_days' => $productData['lead_time_days'],
                    'safety_days' => $productData['safety_days'],
                    'safety_rule_type' => 'days',
                    'transport_rule_type' => 'standard',
                    'order_enabled' => true,
                ]
            );
        }

        $supplierTemplate = FormTemplate::query()->updateOrCreate(
            [
                'company_id' => $company->getKey(),
                'code' => 'supplier-order-form',
                'version' => '1.0',
            ],
            [
                'name' => 'Supplier Order Form',
                'context_type' => 'supplier_order',
                'supplier_id' => $manufacturer->getKey(),
                'carrier_id' => null,
                'format_type' => 'portal_manual',
                'fields_schema_json' => [
                    'fields' => ['order_number', 'sku', 'quantity', 'ready_date'],
                ],
                'mapping_rules_json' => [
                    'order_number' => 'supplier_order.order_number',
                    'sku' => 'supplier_order_items.product.sku',
                    'quantity' => 'supplier_order_items.ordered_quantity',
                    'ready_date' => 'supplier_confirmation.ready_date',
                ],
                'validation_rules_json' => [
                    'order_number' => ['required'],
                    'sku' => ['required'],
                    'quantity' => ['required', 'numeric'],
                ],
                'renderer_config_json' => [
                    'renderer' => 'supplier_portal',
                ],
                'is_active' => true,
            ]
        );

        $carrierTemplate = FormTemplate::query()->updateOrCreate(
            [
                'company_id' => $company->getKey(),
                'code' => 'carrier-quote-request',
                'version' => '1.0',
            ],
            [
                'name' => 'Carrier Quote Request',
                'context_type' => 'carrier_quote',
                'supplier_id' => null,
                'carrier_id' => $carrier->getKey(),
                'format_type' => 'internal_html',
                'fields_schema_json' => [
                    'fields' => ['pickup_date', 'delivery_date', 'price', 'currency'],
                ],
                'mapping_rules_json' => [
                    'pickup_date' => 'carrier_quote.pickup_date',
                    'delivery_date' => 'carrier_quote.delivery_date',
                    'price' => 'carrier_quote.price',
                    'currency' => 'carrier_quote.currency',
                ],
                'validation_rules_json' => [
                    'pickup_date' => ['nullable', 'date'],
                    'delivery_date' => ['nullable', 'date'],
                    'price' => ['nullable', 'numeric'],
                    'currency' => ['nullable', 'string'],
                ],
                'renderer_config_json' => [
                    'renderer' => 'email_reply',
                ],
                'is_active' => true,
            ]
        );

        $supplierConfirmationTemplate = FormTemplate::query()->updateOrCreate(
            [
                'company_id' => $company->getKey(),
                'code' => 'supplier-confirmation-form',
                'version' => '1.0',
            ],
            [
                'name' => 'Supplier Confirmation Form',
                'context_type' => 'supplier_confirmation',
                'supplier_id' => $manufacturer->getKey(),
                'carrier_id' => null,
                'format_type' => 'internal_html',
                'fields_schema_json' => [
                    'fields' => [
                        'supplier_reference',
                        'supplier_order_number',
                        'sku',
                        'confirmed_quantity',
                        'ready_date',
                        'shipping_date',
                        'expected_arrival_date',
                        'notes',
                    ],
                ],
                'mapping_rules_json' => [
                    'supplier_reference' => 'supplier_confirmation.supplier_reference',
                    'supplier_order_number' => 'supplier_order.order_number',
                    'sku' => 'supplier_confirmation_items.product.sku',
                    'confirmed_quantity' => 'supplier_confirmation_items.confirmed_quantity',
                    'ready_date' => 'supplier_confirmation.ready_date',
                    'shipping_date' => 'supplier_confirmation.shipping_date',
                    'expected_arrival_date' => 'supplier_confirmation.expected_arrival_date',
                    'notes' => 'supplier_confirmation_items.notes',
                ],
                'validation_rules_json' => [
                    'supplier_reference' => ['nullable', 'string'],
                    'supplier_order_number' => ['required', 'string'],
                    'sku' => ['required', 'string'],
                    'confirmed_quantity' => ['required', 'numeric'],
                    'ready_date' => ['nullable', 'date'],
                    'shipping_date' => ['nullable', 'date'],
                    'expected_arrival_date' => ['nullable', 'date'],
                    'notes' => ['nullable', 'string'],
                ],
                'renderer_config_json' => [
                    'renderer' => 'internal_review',
                ],
                'is_active' => true,
            ]
        );

        $carrierQuoteFormTemplate = FormTemplate::query()->updateOrCreate(
            [
                'company_id' => $company->getKey(),
                'code' => 'carrier-quote-form',
                'version' => '1.0',
            ],
            [
                'name' => 'Carrier Quote Form',
                'context_type' => 'carrier_quote',
                'supplier_id' => null,
                'carrier_id' => $carrier->getKey(),
                'format_type' => 'internal_html',
                'fields_schema_json' => [
                    'fields' => [
                        'carrier_name',
                        'price',
                        'currency',
                        'pickup_date',
                        'delivery_date',
                        'transit_days',
                        'conditions',
                        'notes',
                    ],
                ],
                'mapping_rules_json' => [
                    'carrier_name' => 'carrier.name',
                    'price' => 'carrier_quote.price',
                    'currency' => 'carrier_quote.currency',
                    'pickup_date' => 'carrier_quote.pickup_date',
                    'delivery_date' => 'carrier_quote.delivery_date',
                    'transit_days' => 'carrier_quote.transit_days',
                    'conditions' => 'carrier_quote.conditions',
                    'notes' => 'carrier_quote.notes',
                ],
                'validation_rules_json' => [
                    'carrier_name' => ['required', 'string'],
                    'price' => ['required', 'numeric'],
                    'currency' => ['required', 'string', 'size:3'],
                    'pickup_date' => ['nullable', 'date'],
                    'delivery_date' => ['nullable', 'date'],
                    'transit_days' => ['nullable', 'integer'],
                    'conditions' => ['nullable', 'string'],
                    'notes' => ['nullable', 'string'],
                ],
                'renderer_config_json' => [
                    'renderer' => 'internal_review',
                ],
                'is_active' => true,
            ]
        );

        $logisticsUpdateTemplate = FormTemplate::query()->updateOrCreate(
            [
                'company_id' => $company->getKey(),
                'code' => 'logistics-update-form',
                'version' => '1.0',
            ],
            [
                'name' => 'Logistics Update Form',
                'context_type' => 'logistics_update',
                'supplier_id' => $manufacturer->getKey(),
                'carrier_id' => $carrier->getKey(),
                'format_type' => 'internal_html',
                'fields_schema_json' => [
                    'fields' => [
                        'supplier_name',
                        'supplier_order_number',
                        'ready_date',
                        'pickup_date',
                        'delivery_date',
                        'carrier_name',
                        'transport_price',
                        'currency',
                        'status',
                        'notes',
                    ],
                ],
                'mapping_rules_json' => [
                    'supplier_name' => 'supplier.name',
                    'supplier_order_number' => 'supplier_order.order_number',
                    'ready_date' => 'logistics_record.ready_date',
                    'pickup_date' => 'logistics_record.pickup_date',
                    'delivery_date' => 'logistics_record.delivery_date',
                    'carrier_name' => 'carrier.name',
                    'transport_price' => 'logistics_record.transport_price',
                    'currency' => 'logistics_record.currency',
                    'status' => 'logistics_record.status',
                    'notes' => 'logistics_record.notes',
                ],
                'validation_rules_json' => [
                    'supplier_name' => ['nullable', 'string'],
                    'supplier_order_number' => ['required', 'string'],
                    'ready_date' => ['nullable', 'date'],
                    'pickup_date' => ['nullable', 'date'],
                    'delivery_date' => ['nullable', 'date'],
                    'carrier_name' => ['nullable', 'string'],
                    'transport_price' => ['nullable', 'numeric'],
                    'currency' => ['nullable', 'string', 'size:3'],
                    'status' => ['nullable', 'string'],
                    'notes' => ['nullable', 'string'],
                ],
                'renderer_config_json' => [
                    'renderer' => 'internal_review',
                ],
                'is_active' => true,
            ]
        );

        $this->seedTemplateFields($supplierTemplate, [
            ['order_number', 'Order Number', 'text', true, 10, 'Find the purchase order number in the supplier email.'],
            ['sku', 'SKU', 'text', true, 20, 'Extract the product SKU exactly as written.'],
            ['quantity', 'Quantity', 'number', true, 30, 'Extract the confirmed order quantity only.'],
            ['ready_date', 'Ready Date', 'date', false, 40, 'Extract the ready date if explicitly present.'],
        ]);

        $this->seedTemplateFields($carrierTemplate, [
            ['pickup_date', 'Pickup Date', 'date', false, 10, 'Extract the pickup date from the carrier email.'],
            ['delivery_date', 'Delivery Date', 'date', false, 20, 'Extract the delivery date from the carrier email.'],
            ['price', 'Transport Price', 'number', false, 30, 'Extract the quoted transport price.'],
            ['currency', 'Currency', 'text', false, 40, 'Extract the quoted currency code.'],
        ]);

        $this->seedTemplateFields($supplierConfirmationTemplate, [
            ['supplier_reference', 'Supplier Reference', 'text', false, 10, 'Find the supplier confirmation number or reference.'],
            ['supplier_order_number', 'Supplier Order Number', 'text', true, 20, 'Find the purchase order number confirmed by the supplier.'],
            ['sku', 'SKU', 'sku', true, 30, 'Extract the product SKU exactly and match it to a known product.'],
            ['confirmed_quantity', 'Confirmed Quantity', 'decimal', true, 40, 'Find confirmed quantity from supplier reply.'],
            ['ready_date', 'Ready Date', 'date', false, 50, 'Extract the supplier ready date if explicitly present.'],
            ['shipping_date', 'Shipping Date', 'date', false, 60, 'Extract the planned shipping date if present.'],
            ['expected_arrival_date', 'Expected Arrival Date', 'date', false, 70, 'Extract the expected arrival date if present.'],
            ['notes', 'Notes', 'textarea', false, 80, 'Capture important supplier comments that do not fit structured fields.'],
        ]);

        $this->seedTemplateFields($carrierQuoteFormTemplate, [
            ['carrier_name', 'Carrier Name', 'text', true, 10, 'Find the carrier company name in the quote.'],
            ['price', 'Price', 'decimal', true, 20, 'Extract the quoted transport price without currency symbols.'],
            ['currency', 'Currency', 'currency', true, 30, 'Extract the three-letter currency code.'],
            ['pickup_date', 'Pickup Date', 'date', false, 40, 'Extract the quoted pickup date.'],
            ['delivery_date', 'Delivery Date', 'date', false, 50, 'Extract the quoted delivery date.'],
            ['transit_days', 'Transit Days', 'number', false, 60, 'Extract transit days if stated.'],
            ['conditions', 'Conditions', 'textarea', false, 70, 'Extract quote conditions and constraints.'],
            ['notes', 'Notes', 'textarea', false, 80, 'Capture relevant quote notes.'],
        ]);

        $this->seedTemplateFields($logisticsUpdateTemplate, [
            ['supplier_name', 'Supplier Name', 'text', false, 10, 'Extract the supplier name if present.'],
            ['supplier_order_number', 'Supplier Order Number', 'text', true, 20, 'Find the purchase order number related to this logistics update.'],
            ['ready_date', 'Ready Date', 'date', false, 30, 'Extract the ready date.'],
            ['pickup_date', 'Pickup Date', 'date', false, 40, 'Extract the pickup date.'],
            ['delivery_date', 'Delivery Date', 'date', false, 50, 'Extract the delivery date.'],
            ['carrier_name', 'Carrier Name', 'text', false, 60, 'Extract the carrier name.'],
            ['transport_price', 'Transport Price', 'decimal', false, 70, 'Extract transport price if present.'],
            ['currency', 'Currency', 'currency', false, 80, 'Extract the transport currency.'],
            ['status', 'Status', 'select', false, 90, 'Extract logistics status if the email clearly states it.'],
            ['notes', 'Notes', 'textarea', false, 100, 'Capture useful logistics comments.'],
        ]);
    }

    /**
     * @param  list<array{0: string, 1: string, 2: string, 3: bool, 4: int, 5: string}>  $fields
     */
    private function seedTemplateFields(FormTemplate $template, array $fields): void
    {
        foreach ($fields as [$fieldKey, $label, $fieldType, $isRequired, $sortOrder, $hint]) {
            FormTemplateField::query()->updateOrCreate(
                [
                    'form_template_id' => $template->getKey(),
                    'field_key' => $fieldKey,
                ],
                [
                    'label' => $label,
                    'field_type' => $fieldType,
                    'is_required' => $isRequired,
                    'validation_rules_json' => $isRequired ? ['required'] : ['nullable'],
                    'ai_extraction_hint' => $hint,
                    'default_value_json' => null,
                    'sort_order' => $sortOrder,
                ]
            );
        }
    }
}
