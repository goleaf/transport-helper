<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('email and ai extraction indexes load inside supply shell', function (): void {
    $user = User::factory()->create(['role' => UserRole::Admin]);

    $this
        ->actingAs($user)
        ->get(route('supply.emails.index'))
        ->assertOk()
        ->assertSeeText('Emails')
        ->assertSee('aria-label="Supply navigation"', false);

    $this
        ->actingAs($user)
        ->get(route('supply.ai-extractions.index'))
        ->assertOk()
        ->assertSeeText('AI Extractions');
});

test('ai evidence component shows confidence source data and no apply warning', function (): void {
    $view = $this->blade(
        '<x-supply.source-evidence field="Confirmed quantity" suggested="144" normalized="144" final="Not applied" :confidence="0.72" source="Supplier confirmed 144 units." review-reason="Low confidence quantity extraction" />',
    );

    $view
        ->assertSee('Low confidence')
        ->assertSee('Supplier confirmed 144 units.')
        ->assertSee('AI suggestions are not final values')
        ->assertSee('Requires human review');
});
