<?php

namespace Tests\Support;

use App\Enums\EmailDirection;
use App\Enums\FormTemplateFormatType;
use App\Enums\LogisticsStatus;
use App\Enums\SupplierOrderStatus;
use App\Enums\UserRole;
use App\Models\AiEmailExtraction;
use App\Models\Carrier;
use App\Models\CarrierContact;
use App\Models\CarrierQuote;
use App\Models\Company;
use App\Models\EmailMessage;
use App\Models\FormAutofillFieldValue;
use App\Models\FormAutofillRun;
use App\Models\FormTemplate;
use App\Models\FormTemplateField;
use App\Models\LogisticsRecord;
use App\Models\Supplier;
use App\Models\SupplierOrder;
use App\Models\User;

class TransportTestSupport
{
    /**
     * @return array<string, mixed>
     */
    public static function fixture(): array
    {
        $company = Company::factory()->create(['name' => 'Transport Demo Co', 'default_currency' => 'EUR']);
        $supplier = Supplier::factory()->for($company)->create([
            'name' => 'Acme Manufacturing',
            'type' => 'manufacturer',
            'default_currency' => 'EUR',
        ]);
        $supplierOrder = SupplierOrder::factory()->for($company)->for($supplier)->create([
            'order_number' => 'PO-TRANSPORT-1',
            'status' => SupplierOrderStatus::Sent,
            'order_date' => '2026-07-03',
        ]);
        $carrier = Carrier::factory()->for($company)->create([
            'name' => 'Fast Road',
            'code' => 'FAST',
            'default_currency' => 'EUR',
            'reliability_score' => 95,
        ]);
        CarrierContact::factory()->for($carrier)->create(['email' => 'quotes@fast-road.test']);
        $lateCarrier = Carrier::factory()->for($company)->create([
            'name' => 'Cheap Late',
            'code' => 'LATE',
            'default_currency' => 'EUR',
            'reliability_score' => 70,
        ]);
        CarrierContact::factory()->for($lateCarrier)->create(['email' => 'quotes@cheap-late.test']);
        $logisticsRecord = LogisticsRecord::factory()->create([
            'company_id' => $company->id,
            'supplier_id' => $supplier->id,
            'supplier_order_id' => $supplierOrder->id,
            'carrier_id' => null,
            'pickup_date' => '2026-07-10',
            'delivery_date' => '2026-07-20',
            'status' => LogisticsStatus::Planned,
            'transport_price' => null,
        ]);
        $email = EmailMessage::factory()->for($company)->create([
            'direction' => EmailDirection::Inbound,
            'from_email' => 'transport@carrier.test',
            'subject' => 'Transport quote PO-TRANSPORT-1',
            'body_text' => 'Fast Road offers 500 EUR, pickup 2026-07-10, delivery 2026-07-20.',
            'related_supplier_id' => $supplier->id,
            'related_supplier_order_id' => $supplierOrder->id,
            'status' => 'linked',
        ]);
        $user = User::factory()->create(['role' => UserRole::Admin]);

        return compact('company', 'supplier', 'supplierOrder', 'carrier', 'lateCarrier', 'logisticsRecord', 'email', 'user');
    }

    public static function quote(array $fixture, array $overrides = []): CarrierQuote
    {
        return CarrierQuote::factory()->create(array_replace([
            'company_id' => $fixture['company']->id,
            'supplier_order_id' => $fixture['supplierOrder']->id,
            'carrier_id' => $fixture['carrier']->id,
            'email_message_id' => null,
            'price' => 500,
            'currency' => 'EUR',
            'pickup_date' => '2026-07-10',
            'delivery_date' => '2026-07-20',
            'transit_days' => 10,
            'reliability_score' => 95,
            'status' => 'received',
            'created_from_ai_extraction_id' => null,
            'created_from_form_autofill_run_id' => null,
            'source_type' => 'manual',
            'source_id' => null,
            'selected_by_user_id' => null,
            'selected_at' => null,
        ], $overrides));
    }

    public static function acceptedAiExtraction(array $fixture, array $carrierQuote = []): AiEmailExtraction
    {
        return AiEmailExtraction::factory()->create([
            'email_message_id' => $fixture['email']->id,
            'output_json' => [
                'email_type' => 'transport_quote',
                'supplier_order_number' => 'PO-TRANSPORT-1',
                'carrier_quote' => array_replace([
                    'carrier_name' => 'Fast Road',
                    'price' => '500 EUR',
                    'currency' => 'eur',
                    'pickup_date' => '2026-07-10',
                    'delivery_date' => '2026-07-20',
                    'transit_days' => 10,
                    'conditions' => 'Standard trailer',
                    'source_excerpt' => '500 EUR delivery 2026-07-20',
                    'confidence' => 0.91,
                ], $carrierQuote),
            ],
            'confidence' => 91,
            'accepted_at' => now(),
            'rejected_at' => null,
        ]);
    }

    public static function carrierQuoteFormRun(array $fixture, string $status = 'validated', string $contextType = 'carrier_quote'): FormAutofillRun
    {
        $template = FormTemplate::factory()->for($fixture['company'])->create([
            'name' => 'Carrier Quote Form',
            'code' => 'carrier_quote_v1',
            'context_type' => $contextType,
            'format_type' => FormTemplateFormatType::InternalHtml,
            'is_active' => true,
        ]);

        foreach ([
            ['supplier_order_number', 'Supplier order number', 'text', true, 'PO-TRANSPORT-1'],
            ['carrier_name', 'Carrier name', 'text', true, 'Fast Road'],
            ['price', 'Price', 'decimal', true, 500],
            ['currency', 'Currency', 'currency', true, 'EUR'],
            ['pickup_date', 'Pickup date', 'date', false, '2026-07-10'],
            ['delivery_date', 'Delivery date', 'date', true, '2026-07-20'],
            ['transit_days', 'Transit days', 'number', false, 10],
            ['conditions', 'Conditions', 'textarea', false, 'Standard trailer'],
        ] as $index => [$key, $label, $type, $required, $value]) {
            FormTemplateField::factory()->for($template)->create([
                'field_key' => $key,
                'label' => $label,
                'field_type' => $type,
                'is_required' => $required,
                'sort_order' => ($index + 1) * 10,
            ]);
        }

        $run = FormAutofillRun::factory()->create([
            'company_id' => $fixture['company']->id,
            'email_message_id' => $fixture['email']->id,
            'form_template_id' => $template->id,
            'status' => $status,
            'confidence' => 92,
            'created_by_user_id' => $fixture['user']->id,
            'reviewed_by_user_id' => $fixture['user']->id,
        ]);

        foreach ([
            'supplier_order_number' => 'PO-TRANSPORT-1',
            'carrier_name' => 'Fast Road',
            'price' => 500,
            'currency' => 'EUR',
            'pickup_date' => '2026-07-10',
            'delivery_date' => '2026-07-20',
            'transit_days' => 10,
            'conditions' => 'Standard trailer',
        ] as $fieldKey => $value) {
            FormAutofillFieldValue::factory()->create([
                'form_autofill_run_id' => $run->id,
                'field_key' => $fieldKey,
                'extracted_value' => $value,
                'normalized_value' => $value,
                'final_value' => $value,
                'source_excerpt' => $fieldKey.' excerpt',
                'requires_review' => false,
                'accepted_by_user_id' => $fixture['user']->id,
                'accepted_at' => now(),
            ]);
        }

        return $run->refresh();
    }
}
