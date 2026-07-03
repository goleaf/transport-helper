<?php

use App\Contracts\AI\AiEmailFormExtractorInterface;
use App\Enums\EmailDirection;
use App\Enums\FormAutofillRunStatus;
use App\Enums\FormTemplateContextType;
use App\Enums\FormTemplateFormatType;
use App\Enums\LogisticsStatus;
use App\Enums\SupplierOrderStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\CarrierQuote;
use App\Models\Company;
use App\Models\EmailAccount;
use App\Models\EmailMessage;
use App\Models\FormAutofillRun;
use App\Models\FormTemplate;
use App\Models\FormTemplateField;
use App\Models\LogisticsRecord;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierConfirmation;
use App\Models\SupplierOrder;
use App\Models\SupplierOrderItem;
use App\Models\User;
use App\Services\FormAutofill\EmailFormAutofillService;
use App\Services\FormAutofill\FormAutofillApplyService;
use App\Services\FormAutofill\FormAutofillReviewService;
use App\Services\FormAutofill\FormRenderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

function makeEmailFormAutofillWorkflowFixture(string $contextType = 'supplier_confirmation'): array
{
    $company = Company::factory()->create(['name' => 'Demo Supply Co']);
    $supplier = Supplier::factory()->for($company)->create([
        'name' => 'Acme Manufacturing',
        'type' => 'manufacturer',
    ]);
    $product = Product::factory()->for($company)->create([
        'sku' => 'AX-150',
        'name' => 'Axle Bearing 150',
    ]);
    $supplierOrder = SupplierOrder::factory()->create([
        'company_id' => $company->getKey(),
        'supplier_id' => $supplier->getKey(),
        'order_proposal_id' => null,
        'order_number' => 'PO-AUTOFILL-1',
        'status' => SupplierOrderStatus::Sent,
    ]);
    $supplierOrderItem = SupplierOrderItem::factory()->create([
        'supplier_order_id' => $supplierOrder->getKey(),
        'product_id' => $product->getKey(),
        'ordered_quantity' => 156,
    ]);
    $emailAccount = EmailAccount::factory()->for($company)->create([
        'provider' => 'manual',
        'email_address' => 'supply@example.test',
    ]);
    $email = EmailMessage::factory()->create([
        'company_id' => $company->getKey(),
        'email_account_id' => $emailAccount->getKey(),
        'direction' => EmailDirection::Inbound,
        'message_id' => 'autofill-message-1',
        'thread_id' => 'autofill-thread-1',
        'from_email' => 'orders@acme.test',
        'subject' => 'Confirmation for PO-AUTOFILL-1',
        'body_text' => 'Confirmation no. CONF-88421. AX-150 confirmed 156 pcs. Ready 2026-07-10.',
        'related_supplier_id' => $supplier->getKey(),
        'related_supplier_order_id' => $supplierOrder->getKey(),
        'status' => 'received',
    ]);
    $template = createEmailFormAutofillTemplate($company, $supplier, $contextType);
    $user = User::factory()->create(['role' => UserRole::SupplyManager]);

    return compact('company', 'supplier', 'product', 'supplierOrder', 'supplierOrderItem', 'emailAccount', 'email', 'template', 'user');
}

function createEmailFormAutofillTemplate(Company $company, Supplier $supplier, string $contextType): FormTemplate
{
    $template = FormTemplate::factory()->create([
        'company_id' => $company->getKey(),
        'supplier_id' => $contextType === FormTemplateContextType::CarrierQuote->value ? null : $supplier->getKey(),
        'carrier_id' => null,
        'name' => str($contextType)->replace('_', ' ')->title()->append(' Template')->toString(),
        'code' => $contextType.'-template',
        'context_type' => $contextType,
        'format_type' => FormTemplateFormatType::InternalHtml,
        'fields_schema_json' => ['fields' => array_column(emailFormAutofillFieldDefinitions($contextType), 'field_key')],
        'validation_rules_json' => [],
        'mapping_rules_json' => [],
        'renderer_config_json' => ['renderer' => 'internal_review'],
        'is_active' => true,
    ]);

    foreach (emailFormAutofillFieldDefinitions($contextType) as $field) {
        FormTemplateField::factory()->create([
            'form_template_id' => $template->getKey(),
            'field_key' => $field['field_key'],
            'label' => $field['label'],
            'field_type' => $field['field_type'],
            'is_required' => $field['is_required'],
            'validation_rules_json' => $field['is_required'] ? ['required'] : ['nullable'],
            'ai_extraction_hint' => $field['hint'],
            'sort_order' => $field['sort_order'],
        ]);
    }

    return $template;
}

/**
 * @return list<array{field_key: string, label: string, field_type: string, is_required: bool, sort_order: int, hint: string}>
 */
function emailFormAutofillFieldDefinitions(string $contextType): array
{
    return match ($contextType) {
        FormTemplateContextType::CarrierQuote->value => [
            ['field_key' => 'carrier_name', 'label' => 'Carrier Name', 'field_type' => 'text', 'is_required' => true, 'sort_order' => 10, 'hint' => 'Find carrier company name.'],
            ['field_key' => 'price', 'label' => 'Price', 'field_type' => 'decimal', 'is_required' => true, 'sort_order' => 20, 'hint' => 'Find transport quote price.'],
            ['field_key' => 'currency', 'label' => 'Currency', 'field_type' => 'currency', 'is_required' => true, 'sort_order' => 30, 'hint' => 'Find currency.'],
            ['field_key' => 'pickup_date', 'label' => 'Pickup Date', 'field_type' => 'date', 'is_required' => false, 'sort_order' => 40, 'hint' => 'Find pickup date.'],
            ['field_key' => 'delivery_date', 'label' => 'Delivery Date', 'field_type' => 'date', 'is_required' => false, 'sort_order' => 50, 'hint' => 'Find delivery date.'],
            ['field_key' => 'transit_days', 'label' => 'Transit Days', 'field_type' => 'number', 'is_required' => false, 'sort_order' => 60, 'hint' => 'Find transit days.'],
            ['field_key' => 'conditions', 'label' => 'Conditions', 'field_type' => 'textarea', 'is_required' => false, 'sort_order' => 70, 'hint' => 'Find conditions.'],
            ['field_key' => 'notes', 'label' => 'Notes', 'field_type' => 'textarea', 'is_required' => false, 'sort_order' => 80, 'hint' => 'Find notes.'],
        ],
        FormTemplateContextType::LogisticsUpdate->value => [
            ['field_key' => 'supplier_name', 'label' => 'Supplier Name', 'field_type' => 'text', 'is_required' => false, 'sort_order' => 10, 'hint' => 'Find supplier name.'],
            ['field_key' => 'supplier_order_number', 'label' => 'Supplier Order Number', 'field_type' => 'text', 'is_required' => true, 'sort_order' => 20, 'hint' => 'Find order number.'],
            ['field_key' => 'ready_date', 'label' => 'Ready Date', 'field_type' => 'date', 'is_required' => false, 'sort_order' => 30, 'hint' => 'Find ready date.'],
            ['field_key' => 'pickup_date', 'label' => 'Pickup Date', 'field_type' => 'date', 'is_required' => false, 'sort_order' => 40, 'hint' => 'Find pickup date.'],
            ['field_key' => 'delivery_date', 'label' => 'Delivery Date', 'field_type' => 'date', 'is_required' => false, 'sort_order' => 50, 'hint' => 'Find delivery date.'],
            ['field_key' => 'carrier_name', 'label' => 'Carrier Name', 'field_type' => 'text', 'is_required' => false, 'sort_order' => 60, 'hint' => 'Find carrier.'],
            ['field_key' => 'transport_price', 'label' => 'Transport Price', 'field_type' => 'decimal', 'is_required' => false, 'sort_order' => 70, 'hint' => 'Find price.'],
            ['field_key' => 'currency', 'label' => 'Currency', 'field_type' => 'currency', 'is_required' => false, 'sort_order' => 80, 'hint' => 'Find currency.'],
            ['field_key' => 'status', 'label' => 'Status', 'field_type' => 'select', 'is_required' => false, 'sort_order' => 90, 'hint' => 'Find logistics status.'],
            ['field_key' => 'notes', 'label' => 'Notes', 'field_type' => 'textarea', 'is_required' => false, 'sort_order' => 100, 'hint' => 'Find notes.'],
        ],
        default => [
            ['field_key' => 'supplier_reference', 'label' => 'Supplier Reference', 'field_type' => 'text', 'is_required' => false, 'sort_order' => 10, 'hint' => 'Find supplier confirmation reference.'],
            ['field_key' => 'supplier_order_number', 'label' => 'Supplier Order Number', 'field_type' => 'text', 'is_required' => true, 'sort_order' => 20, 'hint' => 'Find order number.'],
            ['field_key' => 'sku', 'label' => 'SKU', 'field_type' => 'sku', 'is_required' => true, 'sort_order' => 30, 'hint' => 'Find SKU.'],
            ['field_key' => 'confirmed_quantity', 'label' => 'Confirmed Quantity', 'field_type' => 'decimal', 'is_required' => true, 'sort_order' => 40, 'hint' => 'Find confirmed quantity from supplier reply.'],
            ['field_key' => 'ready_date', 'label' => 'Ready Date', 'field_type' => 'date', 'is_required' => false, 'sort_order' => 50, 'hint' => 'Find ready date.'],
            ['field_key' => 'shipping_date', 'label' => 'Shipping Date', 'field_type' => 'date', 'is_required' => false, 'sort_order' => 60, 'hint' => 'Find shipping date.'],
            ['field_key' => 'expected_arrival_date', 'label' => 'Expected Arrival Date', 'field_type' => 'date', 'is_required' => false, 'sort_order' => 70, 'hint' => 'Find arrival date.'],
            ['field_key' => 'notes', 'label' => 'Notes', 'field_type' => 'textarea', 'is_required' => false, 'sort_order' => 80, 'hint' => 'Find notes.'],
        ],
    };
}

function emailFormAutofillOutput(array $overrides = []): array
{
    $default = [
        'form_type' => 'supplier_confirmation',
        'overall_confidence' => 0.96,
        'fields' => [
            'supplier_reference' => [
                'value' => 'CONF-88421',
                'confidence' => 0.94,
                'source_excerpt' => 'Confirmation no. CONF-88421',
                'normalized_value' => 'CONF-88421',
                'warning' => null,
            ],
            'supplier_order_number' => [
                'value' => 'PO-AUTOFILL-1',
                'confidence' => 0.97,
                'source_excerpt' => 'Confirmation for PO-AUTOFILL-1',
                'normalized_value' => 'PO-AUTOFILL-1',
                'warning' => null,
            ],
            'sku' => [
                'value' => 'AX-150',
                'confidence' => 0.97,
                'source_excerpt' => 'AX-150 confirmed',
                'normalized_value' => 'AX-150',
                'warning' => null,
            ],
            'confirmed_quantity' => [
                'value' => 156,
                'confidence' => 0.96,
                'source_excerpt' => '156 pcs',
                'normalized_value' => 156,
                'warning' => null,
            ],
            'ready_date' => [
                'value' => '2026-07-10',
                'confidence' => 0.94,
                'source_excerpt' => 'Ready 2026-07-10',
                'normalized_value' => '2026-07-10',
                'warning' => null,
            ],
            'shipping_date' => [
                'value' => '2026-07-11',
                'confidence' => 0.93,
                'source_excerpt' => 'Ships 2026-07-11',
                'normalized_value' => '2026-07-11',
                'warning' => null,
            ],
            'expected_arrival_date' => [
                'value' => '2026-07-20',
                'confidence' => 0.93,
                'source_excerpt' => 'Arrival 2026-07-20',
                'normalized_value' => '2026-07-20',
                'warning' => null,
            ],
            'notes' => [
                'value' => 'Confirmed by supplier.',
                'confidence' => 0.91,
                'source_excerpt' => 'Confirmed',
                'normalized_value' => 'Confirmed by supplier.',
                'warning' => null,
            ],
        ],
        'warnings' => [],
        'requires_human_review' => false,
        'human_review_reason' => null,
    ];

    if (array_key_exists('fields', $overrides)) {
        $default['fields'] = $overrides['fields'];
        unset($overrides['fields']);
    }

    return array_replace_recursive($default, $overrides);
}

function carrierQuoteAutofillOutput(array $overrides = []): array
{
    return array_replace_recursive([
        'form_type' => 'carrier_quote',
        'overall_confidence' => 0.94,
        'fields' => [
            'carrier_name' => ['value' => 'Express Road', 'confidence' => 0.96, 'source_excerpt' => 'Express Road quote', 'normalized_value' => 'Express Road', 'warning' => null],
            'price' => ['value' => '430.50', 'confidence' => 0.95, 'source_excerpt' => 'EUR 430.50', 'normalized_value' => '430.50', 'warning' => null],
            'currency' => ['value' => 'eur', 'confidence' => 0.95, 'source_excerpt' => 'EUR 430.50', 'normalized_value' => 'EUR', 'warning' => null],
            'pickup_date' => ['value' => '2026-07-15', 'confidence' => 0.93, 'source_excerpt' => 'Pickup 2026-07-15', 'normalized_value' => '2026-07-15', 'warning' => null],
            'delivery_date' => ['value' => '2026-07-18', 'confidence' => 0.93, 'source_excerpt' => 'Delivery 2026-07-18', 'normalized_value' => '2026-07-18', 'warning' => null],
            'transit_days' => ['value' => 3, 'confidence' => 0.91, 'source_excerpt' => '3 days transit', 'normalized_value' => 3, 'warning' => null],
            'conditions' => ['value' => 'Standard road freight.', 'confidence' => 0.9, 'source_excerpt' => 'standard road', 'normalized_value' => 'Standard road freight.', 'warning' => null],
            'notes' => ['value' => 'Valid 7 days.', 'confidence' => 0.9, 'source_excerpt' => 'valid 7 days', 'normalized_value' => 'Valid 7 days.', 'warning' => null],
        ],
        'warnings' => [],
        'requires_human_review' => false,
        'human_review_reason' => null,
    ], $overrides);
}

function logisticsUpdateAutofillOutput(array $overrides = []): array
{
    return array_replace_recursive([
        'form_type' => 'logistics_update',
        'overall_confidence' => 0.93,
        'fields' => [
            'supplier_name' => ['value' => 'Acme Manufacturing', 'confidence' => 0.91, 'source_excerpt' => 'Acme Manufacturing', 'normalized_value' => 'Acme Manufacturing', 'warning' => null],
            'supplier_order_number' => ['value' => 'PO-AUTOFILL-1', 'confidence' => 0.95, 'source_excerpt' => 'PO-AUTOFILL-1', 'normalized_value' => 'PO-AUTOFILL-1', 'warning' => null],
            'ready_date' => ['value' => '2026-07-18', 'confidence' => 0.94, 'source_excerpt' => 'Ready 2026-07-18', 'normalized_value' => '2026-07-18', 'warning' => null],
            'pickup_date' => ['value' => '2026-07-19', 'confidence' => 0.94, 'source_excerpt' => 'Pickup 2026-07-19', 'normalized_value' => '2026-07-19', 'warning' => null],
            'delivery_date' => ['value' => '2026-07-22', 'confidence' => 0.94, 'source_excerpt' => 'Delivery 2026-07-22', 'normalized_value' => '2026-07-22', 'warning' => null],
            'carrier_name' => ['value' => 'Express Road', 'confidence' => 0.92, 'source_excerpt' => 'Express Road', 'normalized_value' => 'Express Road', 'warning' => null],
            'transport_price' => ['value' => '520', 'confidence' => 0.91, 'source_excerpt' => 'EUR 520', 'normalized_value' => '520', 'warning' => null],
            'currency' => ['value' => 'EUR', 'confidence' => 0.91, 'source_excerpt' => 'EUR 520', 'normalized_value' => 'EUR', 'warning' => null],
            'status' => ['value' => 'pickup_scheduled', 'confidence' => 0.9, 'source_excerpt' => 'pickup scheduled', 'normalized_value' => 'pickup_scheduled', 'warning' => null],
            'notes' => ['value' => 'Dock appointment confirmed.', 'confidence' => 0.9, 'source_excerpt' => 'Dock appointment', 'normalized_value' => 'Dock appointment confirmed.', 'warning' => null],
        ],
        'warnings' => [],
        'requires_human_review' => false,
        'human_review_reason' => null,
    ], $overrides);
}

function createEmailFormAutofillRunWithOutput(array $fixture, array $output): FormAutofillRun
{
    $mock = Mockery::mock(AiEmailFormExtractorInterface::class);
    $mock->shouldReceive('extract')
        ->once()
        ->andReturn($output);

    app()->instance(AiEmailFormExtractorInterface::class, $mock);

    $result = app(EmailFormAutofillService::class)->createAutofillRun(
        $fixture['email'],
        $fixture['template'],
        [],
        $fixture['user'],
    );

    return $result['run'];
}

function validateEmailFormAutofillRun(FormAutofillRun $run, User $user): FormAutofillRun
{
    app(FormAutofillReviewService::class)->validateRun($run, $user);

    return $run->fresh(['fieldValues', 'formTemplate', 'emailMessage.relatedSupplierOrder']);
}

it('allows a user to create a form template', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['role' => UserRole::SupplyManager]);

    $response = $this->actingAs($user)->post(route('supply.forms.templates.store'), [
        'company_id' => $company->getKey(),
        'name' => 'Supplier Confirmation Form',
        'code' => 'supplier-confirmation-form',
        'context_type' => FormTemplateContextType::SupplierConfirmation->value,
        'format_type' => FormTemplateFormatType::InternalHtml->value,
        'version' => '1.0',
        'is_active' => true,
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('form_templates', [
        'company_id' => $company->getKey(),
        'code' => 'supplier-confirmation-form',
        'context_type' => FormTemplateContextType::SupplierConfirmation->value,
    ]);
});

it('allows a user to create template fields', function () {
    $fixture = makeEmailFormAutofillWorkflowFixture();

    $response = $this->actingAs($fixture['user'])->post(route('supply.forms.templates.fields.store', $fixture['template']), [
        'field_key' => 'portal_reference',
        'label' => 'Portal Reference',
        'field_type' => 'text',
        'is_required' => true,
        'ai_extraction_hint' => 'Find portal reference.',
        'sort_order' => 200,
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('form_template_fields', [
        'form_template_id' => $fixture['template']->getKey(),
        'field_key' => 'portal_reference',
        'is_required' => true,
    ]);
});

it('creates an autofill run from an email', function () {
    $fixture = makeEmailFormAutofillWorkflowFixture();

    $this->mock(AiEmailFormExtractorInterface::class, function (MockInterface $mock): void {
        $mock->shouldReceive('extract')
            ->once()
            ->andReturn(emailFormAutofillOutput());
    });

    $response = $this->actingAs($fixture['user'])->post(route('supply.emails.autofill.preview', $fixture['email']), [
        'form_template_id' => $fixture['template']->getKey(),
    ]);

    $response->assertRedirect();

    expect(FormAutofillRun::query()->count())->toBe(1)
        ->and(FormAutofillRun::query()->firstOrFail()->email_message_id)->toBe($fixture['email']->getKey());
});

it('stores mocked AI extractor fields on the run', function () {
    $fixture = makeEmailFormAutofillWorkflowFixture();

    $run = createEmailFormAutofillRunWithOutput($fixture, emailFormAutofillOutput());

    expect($run->fieldValues)->toHaveCount(8)
        ->and($run->fieldValues->firstWhere('field_key', 'supplier_reference')->final_value)->toBe('CONF-88421')
        ->and($run->fieldValues->firstWhere('field_key', 'confirmed_quantity')->final_value)->toEqual(156)
        ->and($run->fieldValues->firstWhere('field_key', 'confirmed_quantity')->source_excerpt)->toBe('156 pcs');
});

it('marks the run as needing review for a low confidence field', function () {
    $fixture = makeEmailFormAutofillWorkflowFixture();
    $output = emailFormAutofillOutput([
        'fields' => array_replace_recursive(emailFormAutofillOutput()['fields'], [
            'confirmed_quantity' => ['confidence' => 0.5],
        ]),
    ]);

    $run = createEmailFormAutofillRunWithOutput($fixture, $output);

    expect($run->status)->toBe(FormAutofillRunStatus::NeedsReview)
        ->and($run->fieldValues->firstWhere('field_key', 'confirmed_quantity')->requires_review)->toBeTrue();
});

it('marks the run as needing review when a required field is missing', function () {
    $fixture = makeEmailFormAutofillWorkflowFixture();
    $fields = emailFormAutofillOutput()['fields'];
    unset($fields['sku']);

    $run = createEmailFormAutofillRunWithOutput($fixture, emailFormAutofillOutput([
        'fields' => $fields,
    ]));

    expect($run->status)->toBe(FormAutofillRunStatus::NeedsReview)
        ->and($run->fieldValues->firstWhere('field_key', 'sku')->requires_review)->toBeTrue()
        ->and($run->fieldValues->firstWhere('field_key', 'sku')->review_reason)->toContain('required_field_missing');
});

it('allows a user to update an extracted field final value', function () {
    $fixture = makeEmailFormAutofillWorkflowFixture();
    $run = createEmailFormAutofillRunWithOutput($fixture, emailFormAutofillOutput());
    $field = $run->fieldValues->firstWhere('field_key', 'confirmed_quantity');

    $response = $this->actingAs($fixture['user'])->post(route('supply.form-autofill-runs.fields.update', [$run, $field]), [
        'final_value' => '144',
    ]);

    $response->assertRedirect();

    expect($field->fresh()->final_value)->toEqual(144)
        ->and($field->fresh()->requires_review)->toBeFalse();
});

it('writes an audit log when a user updates a field', function () {
    $fixture = makeEmailFormAutofillWorkflowFixture();
    $run = createEmailFormAutofillRunWithOutput($fixture, emailFormAutofillOutput());
    $field = $run->fieldValues->firstWhere('field_key', 'confirmed_quantity');

    $this->actingAs($fixture['user'])->post(route('supply.form-autofill-runs.fields.update', [$run, $field]), [
        'final_value' => '144',
    ]);

    expect(AuditLog::query()->where('event_type', 'form_autofill_field_edited')->exists())->toBeTrue();
});

it('checks supplier confirmation application readiness without creating confirmation', function () {
    $fixture = makeEmailFormAutofillWorkflowFixture();
    $fixture['user']->forceFill(['role' => UserRole::Admin])->save();
    $run = validateEmailFormAutofillRun(
        createEmailFormAutofillRunWithOutput($fixture, emailFormAutofillOutput()),
        $fixture['user'],
    );

    $result = app(FormAutofillApplyService::class)->apply($run, $fixture['user']);

    expect($result['can_apply'])->toBeTrue()
        ->and($result['target_action'])->toBe('create_supplier_confirmation')
        ->and(SupplierConfirmation::query()->count())->toBe(0)
        ->and($run->fresh()->status)->toBe(FormAutofillRunStatus::Validated);
});

it('checks carrier quote application readiness without creating a carrier quote', function () {
    $fixture = makeEmailFormAutofillWorkflowFixture(FormTemplateContextType::CarrierQuote->value);
    $fixture['user']->forceFill(['role' => UserRole::Admin])->save();
    $run = validateEmailFormAutofillRun(
        createEmailFormAutofillRunWithOutput($fixture, carrierQuoteAutofillOutput()),
        $fixture['user'],
    );

    $result = app(FormAutofillApplyService::class)->apply($run, $fixture['user']);

    expect($result['can_apply'])->toBeTrue()
        ->and($result['target_action'])->toBe('create_carrier_quote')
        ->and(CarrierQuote::query()->count())->toBe(0);
});

it('checks logistics update readiness without updating logistics records', function () {
    $fixture = makeEmailFormAutofillWorkflowFixture(FormTemplateContextType::LogisticsUpdate->value);
    $fixture['user']->forceFill(['role' => UserRole::Admin])->save();
    $record = LogisticsRecord::factory()->create([
        'company_id' => $fixture['company']->getKey(),
        'supplier_order_id' => $fixture['supplierOrder']->getKey(),
        'supplier_id' => $fixture['supplier']->getKey(),
        'status' => LogisticsStatus::Planned,
    ]);
    $run = validateEmailFormAutofillRun(
        createEmailFormAutofillRunWithOutput($fixture, logisticsUpdateAutofillOutput()),
        $fixture['user'],
    );

    $original = $record->fresh()->only(['ready_date', 'pickup_date', 'delivery_date', 'transport_price', 'status']);
    $result = app(FormAutofillApplyService::class)->apply($run, $fixture['user']);
    $record->refresh();

    expect($result['can_apply'])->toBeTrue()
        ->and($result['target_action'])->toBe('update_logistics_record')
        ->and($record->only(['ready_date', 'pickup_date', 'delivery_date', 'transport_price', 'status']))->toEqual($original);
});

it('does not pass the application gate with unresolved required fields', function () {
    $fixture = makeEmailFormAutofillWorkflowFixture();
    $fields = emailFormAutofillOutput()['fields'];
    unset($fields['sku']);
    $run = createEmailFormAutofillRunWithOutput($fixture, emailFormAutofillOutput(['fields' => $fields]));

    $result = app(FormAutofillApplyService::class)->apply($run, $fixture['user']);

    expect($result['can_apply'])->toBeFalse()
        ->and($result['blocking_reasons'])->toContain('run_not_validated')
        ->and(SupplierConfirmation::query()->count())->toBe(0);
});

it('does not change business records after a run is rejected', function () {
    $fixture = makeEmailFormAutofillWorkflowFixture();
    $run = createEmailFormAutofillRunWithOutput($fixture, emailFormAutofillOutput());

    app(FormAutofillReviewService::class)->rejectRun($run, $fixture['user']);

    expect(SupplierConfirmation::query()->count())->toBe(0)
        ->and($run->fresh()->status)->toBe(FormAutofillRunStatus::Rejected);
});

it('exports a run as JSON', function () {
    Storage::fake();
    $fixture = makeEmailFormAutofillWorkflowFixture();
    $run = createEmailFormAutofillRunWithOutput($fixture, emailFormAutofillOutput());

    $output = app(FormRenderService::class)->exportJson($run, $fixture['user']);

    Storage::assertExists($output->stored_path);
    expect($output->output_type)->toBe('json')
        ->and($output->content_json['fields'])->toHaveCount(8);
});

it('exports a run as CSV', function () {
    Storage::fake();
    $fixture = makeEmailFormAutofillWorkflowFixture();
    $run = createEmailFormAutofillRunWithOutput($fixture, emailFormAutofillOutput());

    $output = app(FormRenderService::class)->exportCsv($run, $fixture['user']);

    Storage::assertExists($output->stored_path);
    expect($output->output_type)->toBe('csv')
        ->and($output->content_json['rows'])->toHaveCount(8);
});

it('does not create DTO classes', function () {
    $dtoFiles = collect(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path())))
        ->filter(fn (SplFileInfo $file): bool => $file->isFile())
        ->map(fn (SplFileInfo $file): string => $file->getFilename())
        ->filter(fn (string $filename): bool => str_contains($filename, 'DTO') || str_contains($filename, 'Dto'))
        ->values();

    expect($dtoFiles)->toBeEmpty();
});

it('does not create an app data directory', function () {
    expect(is_dir(app_path('Data')))->toBeFalse();
});
