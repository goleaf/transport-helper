<?php

use App\Enums\FormAutofillRunStatus;
use App\Enums\FormTemplateContextType;
use App\Models\SupplierConfirmation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\SupplierConfirmationTestSupport;

uses(RefreshDatabase::class);

it('apply form route works', function () {
    $fixture = SupplierConfirmationTestSupport::fixture();
    $run = SupplierConfirmationTestSupport::validatedFormRun($fixture);

    $this->actingAs($fixture['user'])
        ->post(route('supply.form-autofill-runs.apply-supplier-confirmation', $run), ['confirm_apply' => '1'])
        ->assertRedirect();

    expect(SupplierConfirmation::query()->where('created_from_form_autofill_run_id', $run->getKey())->exists())->toBeTrue();
});

it('unvalidated form run cannot be applied', function () {
    $fixture = SupplierConfirmationTestSupport::fixture();
    $run = SupplierConfirmationTestSupport::validatedFormRun($fixture);
    $run->forceFill(['status' => FormAutofillRunStatus::NeedsReview])->save();

    $this->actingAs($fixture['user'])
        ->post(route('supply.form-autofill-runs.apply-supplier-confirmation', $run), ['confirm_apply' => '1'])
        ->assertSessionHasErrors();
});

it('incompatible form context cannot be applied', function () {
    $fixture = SupplierConfirmationTestSupport::fixture();
    $run = SupplierConfirmationTestSupport::validatedFormRun($fixture, FormTemplateContextType::CarrierQuote->value);

    $this->actingAs($fixture['user'])
        ->post(route('supply.form-autofill-runs.apply-supplier-confirmation', $run), ['confirm_apply' => '1'])
        ->assertSessionHasErrors();
});

it('apply form requires confirm apply', function () {
    $fixture = SupplierConfirmationTestSupport::fixture();
    $run = SupplierConfirmationTestSupport::validatedFormRun($fixture);

    $this->actingAs($fixture['user'])
        ->post(route('supply.form-autofill-runs.apply-supplier-confirmation', $run), [])
        ->assertSessionHasErrors(['confirm_apply']);
});
