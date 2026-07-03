<?php

use App\Enums\EmailDirection;
use App\Models\FormAutofillRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FormAutofillTestSupport;

uses(RefreshDatabase::class);

it('shows autofill button setup page and creates preview runs', function () {
    $fixture = FormAutofillTestSupport::fixture();

    $this->actingAs($fixture['user'])
        ->get(route('supply.emails.show', $fixture['email']))
        ->assertOk()
        ->assertSee('Autofill form from this email');

    $this->actingAs($fixture['user'])
        ->get(route('supply.emails.autofill.create', $fixture['email']))
        ->assertOk()
        ->assertSee('Generate autofill preview');

    $this->actingAs($fixture['user'])
        ->post(route('supply.emails.autofill.preview', $fixture['email']), [
            'form_template_id' => $fixture['template']->id,
            'extractor' => 'fake',
            'fake_output' => FormAutofillTestSupport::aiOutput(),
        ])
        ->assertRedirect();

    expect(FormAutofillRun::query()->count())->toBe(1);
});

it('does not create autofill run for outbound email', function () {
    $fixture = FormAutofillTestSupport::fixture();
    $fixture['email']->forceFill(['direction' => EmailDirection::Outbound])->save();

    $this->actingAs($fixture['user'])
        ->post(route('supply.emails.autofill.preview', $fixture['email']), [
            'form_template_id' => $fixture['template']->id,
            'extractor' => 'fake',
            'fake_output' => FormAutofillTestSupport::aiOutput(),
        ])
        ->assertSessionHasErrors();
});
