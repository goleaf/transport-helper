<?php

use App\Models\AiEmailExtraction;
use App\Models\Carrier;
use App\Models\CarrierQuote;
use App\Models\Company;
use App\Models\EmailAccount;
use App\Models\EmailMessage;
use App\Models\FormAutofillFieldValue;
use App\Models\FormAutofillOutput;
use App\Models\FormAutofillRun;
use App\Models\FormTemplate;
use App\Models\FormTemplateField;
use App\Models\Supplier;
use App\Models\SupplierConfirmation;
use App\Models\SupplierOrder;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('migrates the email to form autofill module schema', function () {
    expect(Schema::hasColumns('form_templates', [
        'company_id',
        'name',
        'code',
        'context_type',
        'supplier_id',
        'carrier_id',
        'format_type',
        'version',
        'fields_schema_json',
        'mapping_rules_json',
        'validation_rules_json',
        'renderer_config_json',
        'is_active',
    ]))->toBeTrue()
        ->and(Schema::hasColumns('form_template_fields', [
            'form_template_id',
            'field_key',
            'label',
            'field_type',
            'is_required',
            'validation_rules_json',
            'ai_extraction_hint',
            'default_value_json',
            'sort_order',
        ]))->toBeTrue()
        ->and(Schema::hasColumns('form_autofill_runs', [
            'company_id',
            'email_message_id',
            'form_template_id',
            'ai_email_extraction_id',
            'status',
            'confidence',
            'raw_input_hash',
            'suggested_values_json',
            'validation_errors_json',
            'warnings_json',
            'user_changes_json',
            'created_by_user_id',
            'reviewed_by_user_id',
            'applied_by_user_id',
            'applied_at',
        ]))->toBeTrue()
        ->and(Schema::hasColumns('form_autofill_field_values', [
            'form_autofill_run_id',
            'field_key',
            'extracted_value',
            'normalized_value',
            'final_value',
            'confidence',
            'source_excerpt',
            'requires_review',
            'review_reason',
            'accepted_by_user_id',
            'accepted_at',
        ]))->toBeTrue()
        ->and(Schema::hasColumns('form_autofill_outputs', [
            'form_autofill_run_id',
            'output_type',
            'filename',
            'stored_path',
            'content_json',
            'status',
            'created_by_user_id',
        ]))->toBeTrue();
});

it('connects email, ai extraction, templates, field values, outputs, and applied records', function () {
    $company = Company::factory()->create();
    $supplier = Supplier::factory()->for($company)->create();
    $carrier = Carrier::factory()->for($company)->create();
    $user = User::factory()->create();
    $supplierOrder = SupplierOrder::factory()
        ->for($company)
        ->for($supplier)
        ->create();
    $emailAccount = EmailAccount::factory()->for($company)->create();
    $emailMessage = EmailMessage::factory()
        ->for($company)
        ->for($emailAccount)
        ->for($supplier, 'relatedSupplier')
        ->for($supplierOrder, 'relatedSupplierOrder')
        ->create();
    $aiExtraction = AiEmailExtraction::factory()
        ->for($emailMessage)
        ->for($user, 'reviewedBy')
        ->create([
            'output_json' => [
                'order_number' => $supplierOrder->order_number,
                'quantity' => 156,
            ],
            'confidence' => 93.25,
        ]);
    $template = FormTemplate::factory()
        ->for($company)
        ->for($supplier)
        ->create([
            'carrier_id' => $carrier->getKey(),
            'code' => 'supplier-order-form',
            'context_type' => 'supplier_order',
            'format_type' => 'portal_manual',
            'fields_schema_json' => [
                'fields' => ['order_number', 'quantity'],
            ],
        ]);
    $field = FormTemplateField::factory()
        ->for($template)
        ->create([
            'field_key' => 'quantity',
            'field_type' => 'number',
            'is_required' => true,
        ]);
    $run = FormAutofillRun::factory()
        ->for($company)
        ->for($emailMessage)
        ->for($template)
        ->for($aiExtraction)
        ->for($user, 'createdBy')
        ->create([
            'status' => 'needs_review',
            'suggested_values_json' => [
                'quantity' => 156,
            ],
        ]);
    $value = FormAutofillFieldValue::factory()
        ->for($run)
        ->for($user, 'acceptedBy')
        ->create([
            'field_key' => 'quantity',
            'extracted_value' => '156 pcs',
            'normalized_value' => '156',
            'final_value' => '156',
            'requires_review' => false,
        ]);
    $output = FormAutofillOutput::factory()
        ->for($run)
        ->for($user, 'createdBy')
        ->create([
            'output_type' => 'json',
            'content_json' => [
                'quantity' => 156,
            ],
        ]);

    $confirmation = SupplierConfirmation::factory()
        ->for($company)
        ->for($supplierOrder)
        ->for($emailMessage)
        ->for($aiExtraction, 'aiEmailExtraction')
        ->create([
            'created_from_form_autofill_run_id' => $run->getKey(),
        ]);
    $quote = CarrierQuote::factory()
        ->for($company)
        ->for($supplierOrder)
        ->for($carrier)
        ->for($emailMessage)
        ->for($aiExtraction, 'aiEmailExtraction')
        ->create([
            'created_from_form_autofill_run_id' => $run->getKey(),
        ]);

    expect($template->company->is($company))->toBeTrue()
        ->and($template->supplier->is($supplier))->toBeTrue()
        ->and($template->carrier->is($carrier))->toBeTrue()
        ->and($field->formTemplate->is($template))->toBeTrue()
        ->and($run->emailMessage->is($emailMessage))->toBeTrue()
        ->and($run->aiEmailExtraction->is($aiExtraction))->toBeTrue()
        ->and($run->formTemplate->is($template))->toBeTrue()
        ->and($value->formAutofillRun->is($run))->toBeTrue()
        ->and($value->acceptedBy->is($user))->toBeTrue()
        ->and($output->formAutofillRun->is($run))->toBeTrue()
        ->and($output->createdBy->is($user))->toBeTrue()
        ->and($confirmation->formAutofillRun->is($run))->toBeTrue()
        ->and($quote->formAutofillRun->is($run))->toBeTrue()
        ->and($emailMessage->formAutofillRuns()->count())->toBe(1)
        ->and($aiExtraction->formAutofillRuns()->count())->toBe(1)
        ->and($company->formAutofillRuns()->count())->toBe(1);
});

it('keeps template and run field keys unique inside their parent records', function () {
    $template = FormTemplate::factory()->create();
    $run = FormAutofillRun::factory()->create();

    FormTemplateField::factory()
        ->for($template)
        ->create(['field_key' => 'quantity']);
    FormAutofillFieldValue::factory()
        ->for($run)
        ->create(['field_key' => 'quantity']);

    expect(fn () => FormTemplateField::factory()
        ->for($template)
        ->create(['field_key' => 'quantity']))
        ->toThrow(QueryException::class)
        ->and(fn () => FormAutofillFieldValue::factory()
            ->for($run)
            ->create(['field_key' => 'quantity']))
        ->toThrow(QueryException::class);
});
