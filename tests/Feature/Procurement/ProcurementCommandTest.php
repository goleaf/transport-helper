<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ProcurementTestSupport;

uses(RefreshDatabase::class);

it('procurement audit and budget status commands run', function (): void {
    ProcurementTestSupport::fixture();

    $this->artisan('supply:procurement-rules-audit')
        ->assertSuccessful();

    $this->artisan('supply:budget-status')
        ->assertSuccessful();
});

it('procurement gate command runs', function (): void {
    $fixture = ProcurementTestSupport::fixture();

    $this->artisan('supply:procurement-gate', [
        'type' => 'proposal',
        'id' => $fixture['proposal']->id,
        'action' => 'approve_order_proposal',
    ])->assertSuccessful();
});
