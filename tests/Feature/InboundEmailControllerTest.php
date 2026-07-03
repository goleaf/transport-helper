<?php

require_once __DIR__.'/InboundEmailStage6Support.php';

use App\Enums\UserRole;
use App\Models\AiEmailExtraction;
use App\Models\EmailMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('email index loads', function (): void {
    $fixture = inboundEmailStage6Fixture();

    $this->actingAs($fixture['user'])
        ->get(route('supply.emails.index'))
        ->assertOk()
        ->assertSee('Supply Emails');
});

it('manual email create page loads', function (): void {
    $fixture = inboundEmailStage6Fixture();

    $this->actingAs($fixture['user'])
        ->get(route('supply.emails.create-manual'))
        ->assertOk()
        ->assertSee('Manual Inbound Email');
});

it('manual email store creates email', function (): void {
    $fixture = inboundEmailStage6Fixture();

    $this->actingAs($fixture['user'])
        ->post(route('supply.emails.manual.store'), [
            'company_id' => $fixture['company']->getKey(),
            'email_account_id' => $fixture['emailAccount']->getKey(),
            'from_email' => 'orders@acme.test',
            'subject' => 'PO-20260703-1 confirmation',
            'body_text' => 'Confirmed',
            'received_at' => '2026-07-03 12:00:00',
        ])
        ->assertRedirect();

    expect(EmailMessage::query()->count())->toBe(1);
});

it('email show displays body and related supplier', function (): void {
    $fixture = inboundEmailStage6Fixture();
    $email = inboundEmailStage6Message($fixture);

    $this->actingAs($fixture['user'])
        ->get(route('supply.emails.show', $email))
        ->assertOk()
        ->assertSee('Confirmed SKU-1001')
        ->assertSee('Acme Supplier');
});

it('analyze route creates extraction', function (): void {
    $fixture = inboundEmailStage6Fixture();
    $email = inboundEmailStage6Message($fixture);

    $this->actingAs($fixture['user'])
        ->post(route('supply.emails.analyze', $email), [
            'analyzer' => 'fake',
            'sync' => true,
            'fake_output' => inboundEmailStage6Output(),
        ])
        ->assertRedirect();

    expect(AiEmailExtraction::query()->count())->toBe(1);
});

it('viewer without permission cannot analyze when permissions exist', function (): void {
    $fixture = inboundEmailStage6Fixture();
    $email = inboundEmailStage6Message($fixture);
    $viewer = User::factory()->create(['role' => UserRole::Viewer]);

    $this->actingAs($viewer)
        ->post(route('supply.emails.analyze', $email), ['analyzer' => 'fake'])
        ->assertForbidden();
});

it('email show contains autofill placeholder for next stage', function (): void {
    $fixture = inboundEmailStage6Fixture();
    $email = inboundEmailStage6Message($fixture);

    $this->actingAs($fixture['user'])
        ->get(route('supply.emails.show', $email))
        ->assertOk()
        ->assertSee('Autofill form from this email');
});
