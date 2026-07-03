<?php

test('status badge renders needs review text', function (): void {
    $view = $this->blade('<x-supply.status-badge status="needs_review" />');

    $view
        ->assertSee('Needs review')
        ->assertSee('Human review is required');
});

test('ai confidence badge renders low confidence', function (): void {
    $view = $this->blade('<x-supply.ai-confidence-badge :confidence="0.72" />');

    $view
        ->assertSee('Low confidence')
        ->assertSee('72%');
});

test('human review banner renders reason and action', function (): void {
    $view = $this->blade('<x-supply.human-review-banner reason="Unknown SKU" action="Review product mapping" />');

    $view
        ->assertSee('Requires human review')
        ->assertSee('Unknown SKU')
        ->assertSee('Review product mapping');
});

test('timeline and formula components render operator guidance', function (): void {
    $timeline = $this->blade('<x-supply.t0-t3-timeline t0="2026-07-03" t1="2026-07-10" t2="2026-07-31" t3="2026-08-14" />');

    $timeline
        ->assertSee('T0')
        ->assertSee('T1')
        ->assertSee('T2')
        ->assertSee('T3')
        ->assertSee('Safety stock covers only T2-T3');

    $formula = $this->blade(
        '<x-supply.formula-explanation :explanation="$explanation" />',
        ['explanation' => [
            'formula_steps' => [
                ['name' => 'Raw need', 'formula' => 'Need T1-T2 + Safety - Stock T1', 'calculation' => '120 + 72 - 42', 'value' => 150],
            ],
            'final_result' => 156,
        ]],
    );

    $formula
        ->assertSee('Raw need')
        ->assertSee('Need T1-T2 + Safety - Stock T1')
        ->assertSee('156');
});

test('source evidence and empty state render guided copy', function (): void {
    $evidence = $this->blade(
        '<x-supply.source-evidence field="Ready date" suggested="2026-07-10" normalized="2026-07-10" final="2026-07-11" :confidence="0.81" source="Supplier wrote ready next Friday." review-reason="Date shifted by operator" />',
    );

    $evidence
        ->assertSee('AI suggestion')
        ->assertSee('Final value')
        ->assertSee('Supplier wrote ready next Friday.');

    $empty = $this->blade('<x-supply.empty-state title="No actions" message="Nothing needs review." action-label="Open dashboard" action-url="/supply" />');

    $empty
        ->assertSee('No actions')
        ->assertSee('Nothing needs review.')
        ->assertSee('Open dashboard');
});
