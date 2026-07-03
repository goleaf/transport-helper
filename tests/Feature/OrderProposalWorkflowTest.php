<?php

use App\Enums\OrderProposalItemStatus;
use App\Enums\OrderProposalStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\CalculationRun;
use App\Models\Company;
use App\Models\OrderProposal;
use App\Models\OrderProposalItem;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeOrderProposalWorkflowFixture(array $proposalOverrides = [], array $itemOverrides = []): array
{
    $company = Company::factory()->create(['name' => 'Demo Supply Co']);
    $supplier = Supplier::factory()->for($company)->create([
        'name' => 'Acme Manufacturing',
        'type' => 'manufacturer',
    ]);
    $product = Product::factory()->for($company)->create([
        'sku' => 'AX-150',
        'name' => 'Axle Bearing 150',
    ]);
    $creator = User::factory()->create(['name' => 'Planner']);
    $calculationRun = CalculationRun::factory()->create([
        'company_id' => $company->getKey(),
        'supplier_id' => $supplier->getKey(),
        'calculation_date' => '2026-07-03',
        'formula_version' => 't0-t1-t2-t3-v1',
        'started_by_user_id' => $creator->getKey(),
    ]);

    $proposal = OrderProposal::factory()->create(array_merge([
        'company_id' => $company->getKey(),
        'supplier_id' => $supplier->getKey(),
        'calculation_run_id' => $calculationRun->getKey(),
        'created_by_user_id' => $creator->getKey(),
        'status' => OrderProposalStatus::Draft,
        'total_lines' => 1,
    ], $proposalOverrides));

    $item = OrderProposalItem::factory()->create(array_merge([
        'order_proposal_id' => $proposal->getKey(),
        'product_id' => $product->getKey(),
        't0_date' => '2026-07-03',
        't1_date' => '2026-07-10',
        't2_date' => '2026-07-24',
        't3_date' => '2026-08-07',
        'trend' => 1.2,
        'need_t0_t1' => 48,
        'stock_t1' => 22,
        'need_t1_t2' => 120,
        'safety_stock' => 72,
        'inbound_until_t1' => 0,
        'inbound_t1_t3' => 20,
        'reserved_quantity' => 0,
        'raw_need' => 150,
        'recommended_quantity' => 156,
        'approved_quantity' => null,
        'warnings_json' => ['pallet_show_only'],
        'explanation_json' => [
            'dates' => [
                't0_date' => '2026-07-03',
                't1_date' => '2026-07-10',
                't2_date' => '2026-07-24',
                't3_date' => '2026-08-07',
            ],
            'formula_steps' => [
                'raw_need = need_t1_t2 + safety_stock - stock_t1 - inbound_t1_t3 + reserved_quantity',
            ],
            'intermediate_values' => [
                'raw_need' => 150,
                'recommended_quantity' => 156,
            ],
        ],
        'requires_human_review' => true,
        'status' => OrderProposalItemStatus::Draft,
    ], $itemOverrides));

    return compact('company', 'supplier', 'product', 'creator', 'calculationRun', 'proposal', 'item');
}

it('prevents a viewer from approving an order proposal item', function () {
    $fixture = makeOrderProposalWorkflowFixture();
    $viewer = User::factory()->create(['role' => UserRole::Viewer]);

    $this->actingAs($viewer)
        ->post(route('supply.proposals.items.approve', [$fixture['proposal'], $fixture['item']]))
        ->assertForbidden();

    expect($fixture['item']->fresh()->status)->toBe(OrderProposalItemStatus::Draft)
        ->and(AuditLog::query()->where('event_type', 'order_quantity_approved')->exists())->toBeFalse();
});

it('allows a supply manager to approve an order proposal item', function () {
    $fixture = makeOrderProposalWorkflowFixture();
    $manager = User::factory()->create(['role' => UserRole::SupplyManager]);

    $this->actingAs($manager)
        ->post(route('supply.proposals.items.approve', [$fixture['proposal'], $fixture['item']]), [
            'confirmed_review' => true,
        ])
        ->assertRedirect(route('supply.proposals.items.show', [$fixture['proposal'], $fixture['item']]));

    $item = $fixture['item']->fresh();

    expect($item->status)->toBe(OrderProposalItemStatus::Approved)
        ->and((float) $item->approved_quantity)->toBe(156.0)
        ->and($item->requires_human_review)->toBeTrue()
        ->and(AuditLog::query()->where('event_type', 'order_quantity_approved')->exists())->toBeTrue();
});

it('rejects an adjustment without a reason', function () {
    $fixture = makeOrderProposalWorkflowFixture();
    $manager = User::factory()->create(['role' => UserRole::SupplyManager]);

    $this->actingAs($manager)
        ->from(route('supply.proposals.items.show', [$fixture['proposal'], $fixture['item']]))
        ->post(route('supply.proposals.items.adjust', [$fixture['proposal'], $fixture['item']]), [
            'quantity' => 144,
        ])
        ->assertRedirect(route('supply.proposals.items.show', [$fixture['proposal'], $fixture['item']]))
        ->assertSessionHasErrors('reason');

    expect($fixture['item']->fresh()->status)->toBe(OrderProposalItemStatus::Draft);
});

it('adjusts an item with a reason and writes an audit log', function () {
    $fixture = makeOrderProposalWorkflowFixture();
    $manager = User::factory()->create(['role' => UserRole::SupplyManager]);

    $this->actingAs($manager)
        ->post(route('supply.proposals.items.adjust', [$fixture['proposal'], $fixture['item']]), [
            'quantity' => 144,
            'reason' => 'Supplier confirmed a reduced pack quantity.',
        ])
        ->assertRedirect(route('supply.proposals.items.show', [$fixture['proposal'], $fixture['item']]));

    $item = $fixture['item']->fresh();
    $auditLog = AuditLog::query()
        ->where('event_type', 'order_quantity_adjusted')
        ->firstOrFail();

    expect($item->status)->toBe(OrderProposalItemStatus::Adjusted)
        ->and((float) $item->approved_quantity)->toBe(144.0)
        ->and((float) $item->user_adjusted_quantity)->toBe(144.0)
        ->and($item->adjustment_reason)->toBe('Supplier confirmed a reduced pack quantity.')
        ->and($auditLog->user->is($manager))->toBeTrue();
});

it('does not convert a proposal with unresolved items', function () {
    $fixture = makeOrderProposalWorkflowFixture([
        'status' => OrderProposalStatus::Approved,
    ]);
    $manager = User::factory()->create(['role' => UserRole::SupplyManager]);

    $this->actingAs($manager)
        ->from(route('supply.proposals.show', $fixture['proposal']))
        ->post(route('supply.proposals.convert-to-supplier-order', $fixture['proposal']))
        ->assertRedirect(route('supply.proposals.show', $fixture['proposal']))
        ->assertSessionHasErrors('proposal');

    expect(SupplierOrder::query()->count())->toBe(0);
});

it('converts an approved proposal to a supplier order', function () {
    $fixture = makeOrderProposalWorkflowFixture([
        'status' => OrderProposalStatus::Approved,
    ], [
        'status' => OrderProposalItemStatus::Approved,
        'approved_quantity' => 156,
        'requires_human_review' => false,
    ]);
    $manager = User::factory()->create(['role' => UserRole::SupplyManager]);

    $this->actingAs($manager)
        ->post(route('supply.proposals.convert-to-supplier-order', $fixture['proposal']))
        ->assertRedirect(route('supply.supplier-orders.show', SupplierOrder::query()->firstOrFail()));

    $supplierOrder = SupplierOrder::query()
        ->with('items')
        ->firstOrFail();

    expect($supplierOrder->orderProposal->is($fixture['proposal']))->toBeTrue()
        ->and($supplierOrder->items)->toHaveCount(1)
        ->and((float) $supplierOrder->items->first()->ordered_quantity)->toBe(156.0)
        ->and($fixture['proposal']->fresh()->status)->toBe(OrderProposalStatus::ConvertedToSupplierOrder)
        ->and(AuditLog::query()->where('event_type', 'order_proposal_converted_to_supplier_order')->exists())->toBeTrue();
});

it('shows all formula components on the item page', function () {
    $fixture = makeOrderProposalWorkflowFixture();
    $viewer = User::factory()->create(['role' => UserRole::Viewer]);

    $this->actingAs($viewer)
        ->get(route('supply.proposals.items.show', [$fixture['proposal'], $fixture['item']]))
        ->assertOk()
        ->assertSeeText('AX-150')
        ->assertSeeText('Axle Bearing 150')
        ->assertSeeText('T0/T1/T2/T3 timeline')
        ->assertSeeText('trend')
        ->assertSeeText('need_t0_t1')
        ->assertSeeText('stock_t1')
        ->assertSeeText('need_t1_t2')
        ->assertSeeText('safety_stock')
        ->assertSeeText('inbound_until_t1')
        ->assertSeeText('inbound_t1_t3')
        ->assertSeeText('reserved_quantity')
        ->assertSeeText('raw_need')
        ->assertSeeText('recommended_quantity')
        ->assertSeeText('approved_quantity')
        ->assertSeeText('pallet_show_only')
        ->assertSeeText('Human review required.')
        ->assertSeeText('raw_need = need_t1_t2 + safety_stock - stock_t1 - inbound_t1_t3 + reserved_quantity');
});
