<?php

namespace Database\Seeders;

use App\Models\Carrier;
use App\Models\Company;
use App\Models\FormTemplate;
use App\Models\FormTemplateField;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class DemoFormTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company = Company::query()->firstOrCreate(
            ['code' => 'DEMO'],
            [
                'name' => 'Demo Supply Company',
                'timezone' => 'Europe/Vilnius',
                'default_currency' => 'EUR',
            ]
        );

        $supplier = Supplier::query()
            ->where('company_id', $company->getKey())
            ->where('code', 'DEMO-MANUFACTURER')
            ->first();
        $carrier = Carrier::query()
            ->where('company_id', $company->getKey())
            ->where('code', 'DEMO-CARRIER-A')
            ->first();

        $supplierConfirmation = $this->template($company, [
            'name' => 'Supplier Confirmation Form',
            'code' => 'supplier_confirmation_v1',
            'context_type' => 'supplier_confirmation',
            'supplier_id' => $supplier?->getKey(),
            'carrier_id' => null,
            'format_type' => 'internal_html',
        ]);

        $this->fields($supplierConfirmation, [
            ['supplier_reference', 'Supplier Reference', 'text', false, 10, 'Find supplier confirmation reference.'],
            ['supplier_order_number', 'Supplier Order Number', 'text', true, 20, 'Find the purchase order number confirmed by supplier.'],
            ['sku', 'SKU', 'sku', true, 30, 'Extract SKU and match it to known products.'],
            ['confirmed_quantity', 'Confirmed Quantity', 'decimal', true, 40, 'Find confirmed quantity from supplier reply.'],
            ['ready_date', 'Ready Date', 'date', false, 50, 'Extract ready date if stated.'],
            ['shipping_date', 'Shipping Date', 'date', false, 60, 'Extract planned shipping date if stated.'],
            ['expected_arrival_date', 'Expected Arrival Date', 'date', false, 70, 'Extract expected arrival date if stated.'],
            ['notes', 'Notes', 'textarea', false, 80, 'Capture supplier notes.'],
        ]);

        $carrierQuote = $this->template($company, [
            'name' => 'Carrier Quote Form',
            'code' => 'carrier_quote_v1',
            'context_type' => 'carrier_quote',
            'supplier_id' => null,
            'carrier_id' => $carrier?->getKey(),
            'format_type' => 'internal_html',
        ]);

        $this->fields($carrierQuote, [
            ['carrier_name', 'Carrier Name', 'text', true, 10, 'Find carrier company name.'],
            ['price', 'Price', 'decimal', true, 20, 'Extract quoted transport price.'],
            ['currency', 'Currency', 'currency', true, 30, 'Extract three-letter currency code.'],
            ['pickup_date', 'Pickup Date', 'date', false, 40, 'Extract pickup date.'],
            ['delivery_date', 'Delivery Date', 'date', true, 50, 'Extract delivery date.'],
            ['transit_days', 'Transit Days', 'number', false, 60, 'Extract transit days if stated.'],
            ['conditions', 'Conditions', 'textarea', false, 70, 'Extract transport conditions.'],
            ['notes', 'Notes', 'textarea', false, 80, 'Capture relevant quote notes.'],
        ]);

        $logisticsUpdate = $this->template($company, [
            'name' => 'Logistics Update Form',
            'code' => 'logistics_update_v1',
            'context_type' => 'logistics_update',
            'supplier_id' => $supplier?->getKey(),
            'carrier_id' => $carrier?->getKey(),
            'format_type' => 'internal_html',
        ]);

        $this->fields($logisticsUpdate, [
            ['supplier_name', 'Supplier Name', 'text', false, 10, 'Extract supplier name if present.'],
            ['supplier_order_number', 'Supplier Order Number', 'text', true, 20, 'Find related purchase order number.'],
            ['ready_date', 'Ready Date', 'date', false, 30, 'Extract ready date.'],
            ['pickup_date', 'Pickup Date', 'date', false, 40, 'Extract pickup date.'],
            ['delivery_date', 'Delivery Date', 'date', false, 50, 'Extract delivery date.'],
            ['carrier_name', 'Carrier Name', 'text', false, 60, 'Extract carrier name.'],
            ['transport_price', 'Transport Price', 'decimal', false, 70, 'Extract transport price.'],
            ['currency', 'Currency', 'currency', false, 80, 'Extract transport currency.'],
            ['status', 'Status', 'select', false, 90, 'Extract logistics status if explicit.'],
            ['notes', 'Notes', 'textarea', false, 100, 'Capture logistics notes.'],
        ]);
    }

    /**
     * @param  array{name:string,code:string,context_type:string,supplier_id:int|null,carrier_id:int|null,format_type:string}  $attributes
     */
    private function template(Company $company, array $attributes): FormTemplate
    {
        return FormTemplate::query()->updateOrCreate(
            [
                'company_id' => $company->getKey(),
                'code' => $attributes['code'],
                'version' => '1',
            ],
            [
                'name' => $attributes['name'],
                'context_type' => $attributes['context_type'],
                'supplier_id' => $attributes['supplier_id'],
                'carrier_id' => $attributes['carrier_id'],
                'format_type' => $attributes['format_type'],
                'fields_schema_json' => [],
                'mapping_rules_json' => [],
                'validation_rules_json' => [],
                'renderer_config_json' => [],
                'is_active' => true,
            ]
        );
    }

    /**
     * @param  list<array{0:string,1:string,2:string,3:bool,4:int,5:string}>  $fields
     */
    private function fields(FormTemplate $template, array $fields): void
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
