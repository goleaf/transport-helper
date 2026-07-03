<?php

use App\Enums\LogisticsStatus;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\LogisticsTestSupport;

uses(RefreshDatabase::class);

it('logistics index show edit pages load', function () {
    $fixture = LogisticsTestSupport::fixture();

    $this->actingAs($fixture['user'])->get(route('supply.logistics.index'))->assertSuccessful()->assertSee('Logistics');
    $this->actingAs($fixture['user'])->get(route('supply.logistics.show', $fixture['logisticsRecord']))->assertSuccessful()->assertSee('PO-LOG-1001');
    $this->actingAs($fixture['user'])->get(route('supply.logistics.edit', $fixture['logisticsRecord']))->assertSuccessful()->assertSee('Reason');
});

it('logistics update requires reason and writes audit', function () {
    $fixture = LogisticsTestSupport::fixture();

    $this->actingAs($fixture['user'])
        ->patch(route('supply.logistics.update', $fixture['logisticsRecord']), [
            'status' => LogisticsStatus::Delayed->value,
        ])
        ->assertSessionHasErrors('reason');

    $this->actingAs($fixture['user'])
        ->patch(route('supply.logistics.update', $fixture['logisticsRecord']), [
            'status' => LogisticsStatus::Delayed->value,
            'delivery_date' => '2026-07-25',
            'reason' => 'Carrier update.',
        ])
        ->assertRedirectToRoute('supply.logistics.show', $fixture['logisticsRecord']);

    expect(AuditLog::query()->where('event_type', 'logistics_manual_update')->exists())->toBeTrue();
});
