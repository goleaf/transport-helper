<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('proposal index loads inside supply shell', function (): void {
    $user = User::factory()->create(['role' => UserRole::Admin]);

    $this
        ->actingAs($user)
        ->get(route('supply.proposals.index'))
        ->assertOk()
        ->assertSeeText('Order Proposals')
        ->assertSee('aria-label="Supply navigation"', false);
});

test('proposal workflow components show timeline formula and disabled reason copy', function (): void {
    $view = $this->blade(
        '<x-supply.t0-t3-timeline t0="2026-07-03" t1="2026-07-10" t2="2026-07-31" t3="2026-08-14" />
        <x-supply.formula-explanation :explanation="$explanation" />
        <x-supply.next-action-card label="Approve proposal" description="Resolve all review items first." :enabled="false" disabled-reason="Proposal cannot be approved while unresolved review items remain." severity="high" />',
        ['explanation' => [
            'formula_steps' => [
                ['name' => 'Rounding', 'formula' => 'Round to pack multiple', 'calculation' => '150 -> 156', 'value' => 156],
            ],
            'final_result' => 156,
        ]],
    );

    $view
        ->assertSee('T0/T1/T2/T3 timeline')
        ->assertSee('Safety stock covers only T2-T3')
        ->assertSee('150 -&gt; 156', false)
        ->assertSee('Proposal cannot be approved');
});
