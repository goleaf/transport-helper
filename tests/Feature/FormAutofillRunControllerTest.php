<?php

use App\Services\Forms\EmailFormAutofillService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FormAutofillTestSupport;

uses(RefreshDatabase::class);

it('loads run pages and handles field review validation export and gate routes', function () {
    $fixture = FormAutofillTestSupport::fixture();
    $run = app(EmailFormAutofillService::class)->createAutofillRun($fixture['email'], $fixture['template'], [
        'extractor' => 'fake',
        'fake_output' => FormAutofillTestSupport::aiOutput(),
    ], $fixture['user'])['run']->fresh('fieldValues');
    $field = $run->fieldValues->firstWhere('field_key', 'confirmed_quantity');

    $this->actingAs($fixture['user'])->get(route('supply.form-autofill-runs.index'))->assertOk();
    $this->actingAs($fixture['user'])->get(route('supply.form-autofill-runs.show', $run))
        ->assertOk()
        ->assertSee('Field Review')
        ->assertDontSee('Apply</button>', false);

    $this->actingAs($fixture['user'])->post(route('supply.form-autofill-runs.fields.accept', [$run, $field]))->assertRedirect();
    $this->actingAs($fixture['user'])->post(route('supply.form-autofill-runs.fields.update', [$run, $field]), ['final_value' => '156'])->assertRedirect();
    $this->actingAs($fixture['user'])->post(route('supply.form-autofill-runs.fields.reject', [$run, $field]), ['reason' => 'manual review'])->assertRedirect();
    $this->actingAs($fixture['user'])->post(route('supply.form-autofill-runs.fields.accept', [$run, $field]))->assertRedirect();
    $this->actingAs($fixture['user'])->post(route('supply.form-autofill-runs.validate', $run), ['ignore_optional_review' => true])->assertRedirect();
    $this->actingAs($fixture['user'])->post(route('supply.form-autofill-runs.export', $run->fresh()), ['format' => 'json'])->assertRedirect();
    $this->actingAs($fixture['user'])->post(route('supply.form-autofill-runs.application-check', $run->fresh()), ['confirmation' => true])->assertRedirect();
});

it('requires fields to belong to the selected run', function () {
    $first = FormAutofillTestSupport::fixture();
    $second = FormAutofillTestSupport::fixture();
    $run = app(EmailFormAutofillService::class)->createAutofillRun($first['email'], $first['template'], [
        'extractor' => 'fake',
        'fake_output' => FormAutofillTestSupport::aiOutput(),
    ], $first['user'])['run']->fresh('fieldValues');
    $otherRun = app(EmailFormAutofillService::class)->createAutofillRun($second['email'], $second['template'], [
        'extractor' => 'fake',
        'fake_output' => FormAutofillTestSupport::aiOutput(),
    ], $second['user'])['run']->fresh('fieldValues');

    $this->actingAs($first['user'])
        ->post(route('supply.form-autofill-runs.fields.accept', [$run, $otherRun->fieldValues->first()]))
        ->assertNotFound();
});
