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
