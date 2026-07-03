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

function stage4ControllerFixture(array $proposalOverrides = [], array $itemOverrides = []): array
{
    $company = Company::factory()->create(['name' => 'Demo Supply Company']);
    $supplier = Supplier::factory()->for($company)->create(['name' => 'Demo Manufacturer']);
    $run = CalculationRun::factory()->for($company)->for($supplier)->create([
        'calculation_date' => '2026-07-03',
        'formula_version' => 'v1',
    ]);
    $product = Product::factory()->for($company)->create([
        'sku' => 'SKU-1001',
        'name' => 'Demo Product 1001',
    ]);
    $proposal = OrderProposal::factory()
        ->for($company)
        ->for($supplier)
        ->for($run, 'calculationRun')
        ->create(array_merge([
            'status' => OrderProposalStatus::Draft,
            'total_lines' => 1,
        ], $proposalOverrides));
    $item = OrderProposalItem::factory()
        ->for($proposal, 'orderProposal')
        ->for($product)
        ->create(array_merge([
            'status' => OrderProposalItemStatus::Draft,
            'recommended_quantity' => 156,
            'raw_need' => 150,
            'requires_human_review' => true,
            'explanation_json' => [
                'formula_steps' => [
                    [
                        'name' => 'raw_need',
                        'formula' => 'need_t1_t2 + safety_stock - stock_t1 - inbound_t1_t3 + effective_reserved_quantity',
                        'calculation' => '120 + 72 - 22 - 20 + 0 = 150',
                        'value' => 150,
                    ],
                ],
                'rounding_steps' => [
                    ['name' => 'pack_multiple', 'calculation' => 'ceil(150 / 12) * 12 = 156', 'value' => 156],
                ],
            ],
        ], $itemOverrides));

    return compact('company', 'supplier', 'run', 'product', 'proposal', 'item');
}

it('loads proposal index page', function () {
    $fixture = stage4ControllerFixture();
    $viewer = User::factory()->create(['role' => UserRole::Viewer]);

    $this->actingAs($viewer)
        ->get(route('supply.proposals.index'))
        ->assertOk()
        ->assertSeeText('Order Proposals')
        ->assertSeeText($fixture['supplier']->name);
});

it('loads proposal show page with formula component labels', function () {
    $fixture = stage4ControllerFixture();
    $viewer = User::factory()->create(['role' => UserRole::Viewer]);

    $this->actingAs($viewer)
        ->get(route('supply.proposals.show', $fixture['proposal']))
        ->assertOk()
        ->assertSeeText('Trend')
        ->assertSeeText('Need T0-T1')
        ->assertSeeText('Stock T1')
        ->assertSeeText('Need T1-T2')
        ->assertSeeText('Safety stock')
        ->assertSeeText('Raw need')
        ->assertSeeText('Recommended quantity');
});

it('loads item detail page with timeline and formula values', function () {
    $fixture = stage4ControllerFixture();
    $viewer = User::factory()->create(['role' => UserRole::Viewer]);

    $this->actingAs($viewer)
        ->get(route('supply.proposals.items.show', [$fixture['proposal'], $fixture['item']]))
        ->assertOk()
        ->assertSeeText('T0')
        ->assertSeeText('T1')
        ->assertSeeText('T2')
        ->assertSeeText('T3')
        ->assertSeeText('Safety stock covers only T2-T3')
        ->assertSeeText('150')
        ->assertSeeText('156');
});

it('forbids viewer from approving item', function () {
    $fixture = stage4ControllerFixture(['status' => OrderProposalStatus::Draft], [
        'requires_human_review' => false,
    ]);
    $viewer = User::factory()->create(['role' => UserRole::Viewer]);

    $this->actingAs($viewer)
        ->post(route('supply.proposals.items.approve', [$fixture['proposal'], $fixture['item']]))
        ->assertForbidden();
});

it('allows supply manager to approve item', function () {
    $fixture = stage4ControllerFixture(['status' => OrderProposalStatus::Draft], [
        'requires_human_review' => true,
    ]);
    $manager = User::factory()->create(['role' => UserRole::SupplyManager]);

    $this->actingAs($manager)
        ->post(route('supply.proposals.items.approve', [$fixture['proposal'], $fixture['item']]), [
            'confirmed_review' => true,
        ])
        ->assertRedirect(route('supply.proposals.items.show', [$fixture['proposal'], $fixture['item']]));

    expect($fixture['item']->fresh()->status)->toBe(OrderProposalItemStatus::Approved);
});

it('requires reason on adjust route', function () {
    $fixture = stage4ControllerFixture(['status' => OrderProposalStatus::Draft], [
        'requires_human_review' => false,
    ]);
    $manager = User::factory()->create(['role' => UserRole::SupplyManager]);

    $this->actingAs($manager)
        ->from(route('supply.proposals.items.show', [$fixture['proposal'], $fixture['item']]))
        ->post(route('supply.proposals.items.adjust', [$fixture['proposal'], $fixture['item']]), [
            'quantity' => 144,
        ])
        ->assertRedirect(route('supply.proposals.items.show', [$fixture['proposal'], $fixture['item']]))
        ->assertSessionHasErrors('reason');
});

it('adjust route updates item and audit log', function () {
    $fixture = stage4ControllerFixture();
    $manager = User::factory()->create(['role' => UserRole::SupplyManager]);

    $this->actingAs($manager)
        ->post(route('supply.proposals.items.adjust', [$fixture['proposal'], $fixture['item']]), [
            'quantity' => 144,
            'reason' => 'Manual correction after supplier call',
        ])
        ->assertRedirect(route('supply.proposals.items.show', [$fixture['proposal'], $fixture['item']]));

    expect($fixture['item']->fresh()->status)->toBe(OrderProposalItemStatus::Adjusted)
        ->and(AuditLog::query()->where('event_type', 'order_quantity_adjusted')->exists())->toBeTrue();
});

it('requires reason on reject route', function () {
    $fixture = stage4ControllerFixture();
    $manager = User::factory()->create(['role' => UserRole::SupplyManager]);

    $this->actingAs($manager)
        ->from(route('supply.proposals.items.show', [$fixture['proposal'], $fixture['item']]))
        ->post(route('supply.proposals.items.reject', [$fixture['proposal'], $fixture['item']]))
        ->assertRedirect(route('supply.proposals.items.show', [$fixture['proposal'], $fixture['item']]))
        ->assertSessionHasErrors('reason');
});

it('blocks proposal approval with unresolved items', function () {
    $fixture = stage4ControllerFixture();
    $manager = User::factory()->create(['role' => UserRole::SupplyManager]);

    $this->actingAs($manager)
        ->from(route('supply.proposals.show', $fixture['proposal']))
        ->post(route('supply.proposals.approve', $fixture['proposal']), ['confirmation' => true])
        ->assertRedirect(route('supply.proposals.show', $fixture['proposal']))
        ->assertSessionHasErrors('proposal');
});

it('approves proposal through route', function () {
    $fixture = stage4ControllerFixture([], [
        'status' => OrderProposalItemStatus::Approved,
        'approved_quantity' => 156,
        'requires_human_review' => false,
    ]);
    $manager = User::factory()->create(['role' => UserRole::SupplyManager]);

    $this->actingAs($manager)
        ->post(route('supply.proposals.approve', $fixture['proposal']), ['confirmation' => true])
        ->assertRedirect(route('supply.proposals.show', $fixture['proposal']));

    expect($fixture['proposal']->fresh()->status)->toBe(OrderProposalStatus::Approved);
});

it('convert route creates supplier order', function () {
    $fixture = stage4ControllerFixture(['status' => OrderProposalStatus::Approved], [
        'status' => OrderProposalItemStatus::Approved,
        'approved_quantity' => 156,
        'requires_human_review' => false,
    ]);
    $manager = User::factory()->create(['role' => UserRole::SupplyManager]);

    $this->actingAs($manager)
        ->post(route('supply.proposals.convert-to-supplier-order', $fixture['proposal']), ['confirmation' => true])
        ->assertRedirect(route('supply.proposals.show', $fixture['proposal']));

    expect(SupplierOrder::query()->count())->toBe(1);
});

it('returns 404 when item does not belong to proposal', function () {
    $fixture = stage4ControllerFixture();
    $other = stage4ControllerFixture();
    $viewer = User::factory()->create(['role' => UserRole::Viewer]);

    $this->actingAs($viewer)
        ->get(route('supply.proposals.items.show', [$fixture['proposal'], $other['item']]))
        ->assertNotFound();
});
