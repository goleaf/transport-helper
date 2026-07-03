<?php

use App\Enums\EmailDirection;
use App\Enums\UserRole;
use App\Models\AiEmailExtraction;
use App\Models\Company;
use App\Models\EmailMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('extraction index loads', function () {
    $fixture = stage6ExtractionControllerFixture();

    $this->actingAs($fixture['user'])
        ->get(route('supply.ai-extractions.index'))
        ->assertSuccessful()
        ->assertSee('AI Email Extractions');
});

it('extraction show displays output', function () {
    $fixture = stage6ExtractionControllerFixture();

    $this->actingAs($fixture['user'])
        ->get(route('supply.ai-extractions.show', $fixture['extraction']))
        ->assertSuccessful()
        ->assertSee('supplier_confirmation')
        ->assertSee('AI extraction is not applied directly');
});

it('accept extraction route', function () {
    $fixture = stage6ExtractionControllerFixture();

    $this->actingAs($fixture['user'])
        ->post(route('supply.ai-extractions.review', $fixture['extraction']), [
            'decision' => 'accept',
            'note' => 'Reviewed',
        ])
        ->assertRedirect();

    expect($fixture['extraction']->fresh()->accepted_at)->not->toBeNull();
});

it('reject extraction route accepts note', function () {
    $fixture = stage6ExtractionControllerFixture();

    $this->actingAs($fixture['user'])
        ->post(route('supply.ai-extractions.review', $fixture['extraction']), [
            'decision' => 'reject',
            'note' => 'Wrong supplier',
        ])
        ->assertRedirect();

    expect($fixture['extraction']->fresh()->rejected_at)->not->toBeNull();
});

it('needs review route', function () {
    $fixture = stage6ExtractionControllerFixture();

    $this->actingAs($fixture['user'])
        ->post(route('supply.ai-extractions.review', $fixture['extraction']), [
            'decision' => 'needs_review',
            'note' => 'Ambiguous',
        ])
        ->assertRedirect();

    expect($fixture['extraction']->fresh()->requires_human_review)->toBeTrue()
        ->and($fixture['extraction']->fresh()->review_reason)->toBe('Ambiguous');
});

it('extraction show has no apply button', function () {
    $fixture = stage6ExtractionControllerFixture();

    $this->actingAs($fixture['user'])
        ->get(route('supply.ai-extractions.show', $fixture['extraction']))
        ->assertDontSee('Apply supplier confirmation')
        ->assertDontSee('Apply extraction');
});

function stage6ExtractionControllerFixture(): array
{
    $company = Company::factory()->create();
    $email = EmailMessage::factory()->create([
        'company_id' => $company->id,
        'direction' => EmailDirection::Inbound,
        'subject' => 'AI extraction controller',
        'status' => 'needs_review',
    ]);
    $extraction = AiEmailExtraction::factory()->create([
        'email_message_id' => $email->id,
        'output_json' => [
            'email_type' => 'supplier_confirmation',
            'supplier_order_number' => 'PO-1',
            'confirmed_items' => [],
            'dates' => [],
            'carrier_quote' => [],
            'discrepancies' => [],
            'questions_to_supplier' => [],
            'confidence' => 0.7,
            'requires_human_review' => true,
        ],
        'requires_human_review' => true,
    ]);
    $user = User::factory()->create(['role' => UserRole::SupplyManager]);

    return compact('company', 'email', 'extraction', 'user');
}
