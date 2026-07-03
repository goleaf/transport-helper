<?php

use App\Enums\FormTemplateContextType;
use App\Enums\FormTemplateFormatType;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\FormTemplate;
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
