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
use App\Models\User;
use App\Services\Supply\OrderProposals\OrderProposalDecisionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function stage4DecisionFixture(array $proposalOverrides = [], array $itemOverrides = []): array
{
    $company = Company::factory()->create();
    $supplier = Supplier::factory()->for($company)->create();
    $run = CalculationRun::factory()->for($company)->for($supplier)->create();
    $product = Product::factory()->for($company)->create(['sku' => 'SKU-STAGE4']);
    $user = User::factory()->create(['role' => UserRole::SupplyManager]);
    $proposal = OrderProposal::factory()
        ->for($company)
        ->for($supplier)
        ->for($run, 'calculationRun')
        ->create(array_merge([
            'status' => OrderProposalStatus::Draft,
            'created_by_user_id' => $user->id,
        ], $proposalOverrides));
    $item = OrderProposalItem::factory()
        ->for($proposal, 'orderProposal')
        ->for($product)
        ->create(array_merge([
            'status' => OrderProposalItemStatus::Draft,
            'recommended_quantity' => 156,
            'approved_quantity' => null,
            'requires_human_review' => false,
        ], $itemOverrides));

    return compact('company', 'supplier', 'run', 'product', 'user', 'proposal', 'item');
}

it('approves an item and sets approved quantity to recommended quantity', function () {
    $fixture = stage4DecisionFixture();

    $result = app(OrderProposalDecisionService::class)->approveItem($fixture['item'], $fixture['user']);
    $item = $result['item']->fresh();

    expect($item->status)->toBe(OrderProposalItemStatus::Approved)
        ->and((float) $item->approved_quantity)->toBe(156.0)
        ->and(AuditLog::query()->where('event_type', 'order_quantity_approved')->exists())->toBeTrue();
});

it('requires note or confirmation when approving a human review item', function () {
    $fixture = stage4DecisionFixture([], [
        'requires_human_review' => true,
        'status' => OrderProposalItemStatus::NeedsReview,
    ]);

    app(OrderProposalDecisionService::class)->approveItem($fixture['item'], $fixture['user']);
})->throws(ValidationException::class);

it('approves a human review item with explicit confirmation', function () {
    $fixture = stage4DecisionFixture([], [
        'requires_human_review' => true,
        'status' => OrderProposalItemStatus::NeedsReview,
    ]);

    $result = app(OrderProposalDecisionService::class)->approveItem($fixture['item'], $fixture['user'], [
        'confirmed_review' => true,
    ]);

    expect($result['item']->fresh()->status)->toBe(OrderProposalItemStatus::Approved);
});

it('requires a reason when adjusting an item', function () {
    $fixture = stage4DecisionFixture();

    app(OrderProposalDecisionService::class)->adjustItem($fixture['item'], ['quantity' => 144], $fixture['user']);
})->throws(ValidationException::class);

it('adjusts an item with reason and writes audit metadata', function () {
    $fixture = stage4DecisionFixture();

    app(OrderProposalDecisionService::class)->adjustItem($fixture['item'], [
        'quantity' => 144,
        'reason' => 'Supplier package optimization',
    ], $fixture['user']);

    $item = $fixture['item']->fresh();
    $audit = AuditLog::query()->where('event_type', 'order_quantity_adjusted')->firstOrFail();

    expect($item->status)->toBe(OrderProposalItemStatus::Adjusted)
        ->and((float) $item->user_adjusted_quantity)->toBe(144.0)
        ->and((float) $item->approved_quantity)->toBe(144.0)
        ->and($item->adjustment_reason)->toBe('Supplier package optimization')
        ->and($audit->metadata_json['reason'])->toBe('Supplier package optimization');
});

it('allows zero quantity adjustment with reason', function () {
    $fixture = stage4DecisionFixture();

    app(OrderProposalDecisionService::class)->adjustItem($fixture['item'], [
        'quantity' => 0,
        'reason' => 'Do not order this cycle',
    ], $fixture['user']);

    expect($fixture['item']->fresh()->status)->toBe(OrderProposalItemStatus::Adjusted)
        ->and((float) $fixture['item']->fresh()->approved_quantity)->toBe(0.0);
});

it('requires a reason when rejecting an item', function () {
    $fixture = stage4DecisionFixture();

    app(OrderProposalDecisionService::class)->rejectItem($fixture['item'], [], $fixture['user']);
})->throws(ValidationException::class);

it('rejects an item and clears approved quantity', function () {
    $fixture = stage4DecisionFixture([], ['approved_quantity' => 156]);

    app(OrderProposalDecisionService::class)->rejectItem($fixture['item'], [
        'reason' => 'Supplier discontinued this SKU',
    ], $fixture['user']);

    $item = $fixture['item']->fresh();

    expect($item->status)->toBe(OrderProposalItemStatus::Rejected)
        ->and($item->approved_quantity)->toBeNull()
        ->and(AuditLog::query()->where('event_type', 'order_quantity_rejected')->exists())->toBeTrue();
});

it('cannot modify items after proposal conversion', function () {
    $fixture = stage4DecisionFixture([
        'status' => OrderProposalStatus::ConvertedToSupplierOrder,
    ]);

    app(OrderProposalDecisionService::class)->approveItem($fixture['item'], $fixture['user']);
})->throws(ValidationException::class);
