<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('form autofill index loads for admin', function (): void {
    $user = User::factory()->create(['role' => UserRole::Admin]);

    $this
        ->actingAs($user)
        ->get(route('supply.form-autofill-runs.index'))
        ->assertOk()
        ->assertSeeText('Form Autofill Runs');
});

test('form autofill review copy separates extracted normalized and final values', function (): void {
    $view = $this->blade(
        '<x-supply.source-evidence field="Delivery date" suggested="next Friday" normalized="2026-07-10" final="2026-07-11" :confidence="0.84" source="Delivery next Friday if truck available." review-reason="Operator adjusted final date" />',
    );

    $view
        ->assertSee('Suggested')
        ->assertSee('Normalized')
        ->assertSee('Final value')
        ->assertSee('AI suggestions are not final values');
});
