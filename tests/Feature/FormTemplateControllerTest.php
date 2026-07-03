<?php

use App\Enums\FormTemplateContextType;
use App\Enums\FormTemplateFormatType;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\FormTemplate;
use App\Models\FormTemplateField;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('manages templates and fields through controllers', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['role' => UserRole::SupplyManager]);

    $this->actingAs($user)->get(route('supply.forms.templates.index'))->assertOk();

    $this->actingAs($user)->post(route('supply.forms.templates.store'), [
        'company_id' => $company->id,
        'name' => 'Supplier Confirmation Form',
        'code' => 'supplier_confirmation_test',
        'context_type' => FormTemplateContextType::SupplierConfirmation->value,
        'format_type' => FormTemplateFormatType::InternalHtml->value,
        'version' => '1',
        'is_active' => true,
    ])->assertRedirect();

    $template = FormTemplate::query()->firstOrFail();

    $this->actingAs($user)->post(route('supply.forms.templates.fields.store', $template), [
        'field_key' => 'supplier_order_number',
        'label' => 'Supplier order number',
        'field_type' => 'text',
        'is_required' => true,
    ])->assertRedirect();

    $this->actingAs($user)->get(route('supply.forms.templates.show', $template))
        ->assertOk()
        ->assertSee('supplier_order_number');
});

it('shows template details in the structured portal layout', function () {
    $company = Company::factory()->create(['name' => 'North Supply']);
    $template = FormTemplate::factory()->for($company)->create([
        'name' => 'Custom Supplier Email Form',
        'code' => 'custom_email_form_v1',
        'context_type' => FormTemplateContextType::CustomEmailForm,
        'format_type' => FormTemplateFormatType::Json,
        'version' => '2',
        'is_active' => true,
    ]);

    FormTemplateField::factory()->for($template)->create([
        'field_key' => 'subject',
        'label' => 'Subject',
        'field_type' => 'text',
        'is_required' => true,
        'sort_order' => 10,
        'ai_extraction_hint' => 'Extract Subject from supplier email body.',
    ]);

    FormTemplateField::factory()->for($template)->create([
        'field_key' => 'needs_follow_up',
        'label' => 'Needs Follow Up',
        'field_type' => 'boolean',
        'is_required' => false,
        'sort_order' => 20,
    ]);

    $user = User::factory()->create(['role' => UserRole::SupplyManager]);

    $this->actingAs($user)->get(route('supply.forms.templates.show', $template))
        ->assertOk()
        ->assertSee('Custom Supplier Email Form')
        ->assertSee('Template Profile')
        ->assertSee('Fields')
        ->assertSee('Add Field')
        ->assertSee('custom_email_form_v1')
        ->assertSee('North Supply')
        ->assertSee('Custom Email Form')
        ->assertSee('Structured data')
        ->assertSee('subject')
        ->assertSee('Needs Follow Up')
        ->assertSee('Required')
        ->assertSee('Optional');
});

it('allows viewers to inspect templates but blocks field changes', function () {
    $template = FormTemplate::factory()->create();
    $viewer = User::factory()->create(['role' => UserRole::Viewer]);

    $this->actingAs($viewer)->get(route('supply.forms.templates.show', $template))
        ->assertOk();

    $this->actingAs($viewer)->post(route('supply.forms.templates.fields.store', $template), [
        'field_key' => 'blocked_field',
        'label' => 'Blocked field',
        'field_type' => 'text',
    ])->assertForbidden();
});
