<?php

use App\Models\SupplierConfirmation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\SupplierConfirmationTestSupport;

uses(RefreshDatabase::class);

it('apply ai route works', function () {
    $fixture = SupplierConfirmationTestSupport::fixture();
    $extraction = SupplierConfirmationTestSupport::acceptedAiExtraction($fixture);

    $this->actingAs($fixture['user'])
        ->post(route('supply.ai-extractions.apply-supplier-confirmation', $extraction), [
            'confirm_apply' => '1',
            'update_inbound' => '1',
            'update_logistics' => '1',
        ])
        ->assertRedirect();

    expect(SupplierConfirmation::query()->where('created_from_ai_extraction_id', $extraction->getKey())->exists())->toBeTrue();
});

it('unaccepted ai cannot be applied', function () {
    $fixture = SupplierConfirmationTestSupport::fixture();
    $extraction = SupplierConfirmationTestSupport::acceptedAiExtraction($fixture);
    $extraction->forceFill(['accepted_at' => null])->save();

    $this->actingAs($fixture['user'])
        ->post(route('supply.ai-extractions.apply-supplier-confirmation', $extraction), ['confirm_apply' => '1'])
        ->assertSessionHasErrors();
});

it('apply ai requires confirm apply', function () {
    $fixture = SupplierConfirmationTestSupport::fixture();
    $extraction = SupplierConfirmationTestSupport::acceptedAiExtraction($fixture);

    $this->actingAs($fixture['user'])
        ->post(route('supply.ai-extractions.apply-supplier-confirmation', $extraction), [])
        ->assertSessionHasErrors(['confirm_apply']);
});
