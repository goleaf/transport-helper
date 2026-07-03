<?php

use App\Enums\UserRole;
use App\Models\ProcurementApprovalRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ProcurementTestSupport;

uses(RefreshDatabase::class);

it('loads procurement pages and creates policy budget price approval exception and gate result', function (): void {
    $fixture = ProcurementTestSupport::fixture();
    $this->actingAs($fixture['manager']);

    $this->get(route('supply.procurement.policies.index'))->assertOk();
    $this->post(route('supply.procurement.policies.store'), [
        'company_id' => $fixture['company']->id,
        'name' => 'Controller policy',
        'status' => 'active',
        'enforcement_mode' => 'advisory',
        'default_currency' => 'EUR',
        'is_default' => 0,
    ])->assertRedirect();

    $this->get(route('supply.procurement.budgets.index'))->assertOk();
    $this->post(route('supply.procurement.budgets.store'), [
        'company_id' => $fixture['company']->id,
        'name' => 'Controller budget',
        'period_type' => 'monthly',
        'date_from' => '2026-07-01',
        'date_to' => '2026-07-31',
        'currency' => 'EUR',
        'total_amount' => 100,
        'status' => 'active',
    ])->assertRedirect();

    $this->get(route('supply.procurement.prices.index'))->assertOk();
    $this->post(route('supply.procurement.prices.store'), [
        'company_id' => $fixture['company']->id,
        'supplier_id' => $fixture['supplier']->id,
        'product_id' => $fixture['product']->id,
        'currency' => 'EUR',
        'unit_price' => 5,
        'valid_from' => '2026-01-01',
    ])->assertRedirect();

    $this->actingAs($fixture['user']);
    $this->post(route('supply.procurement.approvals.request'), [
        'approvable_type' => 'proposal',
        'approvable_id' => $fixture['proposal']->id,
        'reason' => 'Controller approval request.',
    ])->assertRedirect();

    $approval = ProcurementApprovalRequest::query()->latest('id')->firstOrFail();
    $this->actingAs($fixture['manager']);
    $this->post(route('supply.procurement.approvals.approve', $approval), ['note' => 'Approved.'])->assertRedirect();

    $this->post(route('supply.procurement.exceptions.store'), [
        'exceptable_type' => 'proposal',
        'exceptable_id' => $fixture['proposal']->id,
        'exception_type' => 'missing_price',
        'reason' => 'Controller exception.',
    ])->assertRedirect();

    $this->post(route('supply.procurement.gate'), [
        'type' => 'proposal',
        'id' => $fixture['proposal']->id,
        'action' => 'approve_order_proposal',
    ])->assertOk();

    $this->get(route('supply.procurement.reports.index'))->assertOk();
});

it('viewer cannot approve procurement request', function (): void {
    $fixture = ProcurementTestSupport::fixture();
    $approval = ProcurementApprovalRequest::factory()->for($fixture['company'])->create([
        'approvable_type' => $fixture['proposal']::class,
        'approvable_id' => $fixture['proposal']->id,
        'status' => 'pending',
        'requested_by_user_id' => $fixture['user']->id,
        'reason' => 'Needs manager.',
    ]);
    $viewer = User::factory()->create(['role' => UserRole::Viewer]);

    $this->actingAs($viewer)
        ->post(route('supply.procurement.approvals.approve', $approval), ['note' => 'No authority.'])
        ->assertForbidden();
});
