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
use App\Services\Supply\OrderProposals\OrderProposalApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function stage4ApprovalFixture(array $proposalOverrides = [], array $items = []): array
{
    $company = Company::factory()->create();
    $supplier = Supplier::factory()->for($company)->create();
    $run = CalculationRun::factory()->for($company)->for($supplier)->create();
    $product = Product::factory()->for($company)->create();
    $user = User::factory()->create(['role' => UserRole::SupplyManager]);
    $proposal = OrderProposal::factory()
        ->for($company)
        ->for($supplier)
        ->for($run, 'calculationRun')
        ->create(array_merge(['status' => OrderProposalStatus::Draft], $proposalOverrides));

    foreach ($items as $item) {
        OrderProposalItem::factory()
            ->for($proposal, 'orderProposal')
            ->for($product)
            ->create($item);
    }

    return compact('company', 'supplier', 'run', 'product', 'user', 'proposal');
}

it('cannot approve a proposal with unresolved items', function () {
    $fixture = stage4ApprovalFixture([], [
        ['status' => OrderProposalItemStatus::Draft, 'approved_quantity' => null],
        ['status' => OrderProposalItemStatus::Approved, 'approved_quantity' => 156],
    ]);

    app(OrderProposalApprovalService::class)->approveProposal($fixture['proposal'], $fixture['user']);
})->throws(ValidationException::class);

it('cannot approve a proposal when all items are rejected', function () {
    $fixture = stage4ApprovalFixture([], [
        ['status' => OrderProposalItemStatus::Rejected, 'approved_quantity' => null],
    ]);

    app(OrderProposalApprovalService::class)->approveProposal($fixture['proposal'], $fixture['user']);
})->throws(ValidationException::class);

it('approves a proposal when all items are resolved and an orderable line exists', function () {
    $fixture = stage4ApprovalFixture([], [
        ['status' => OrderProposalItemStatus::Approved, 'approved_quantity' => 156],
        ['status' => OrderProposalItemStatus::Rejected, 'approved_quantity' => null],
    ]);

    $result = app(OrderProposalApprovalService::class)->approveProposal($fixture['proposal'], $fixture['user']);
    $proposal = $result['proposal']->fresh();

    expect($proposal->status)->toBe(OrderProposalStatus::Approved)
        ->and($proposal->approved_by_user_id)->toBe($fixture['user']->id)
        ->and($proposal->approved_at)->not->toBeNull()
        ->and(AuditLog::query()->where('event_type', 'order_proposal_approved')->exists())->toBeTrue()
        ->and(AuditLog::query()->where('event_type', 'order_proposal_status_changed')->exists())->toBeTrue();
});

it('cannot approve a converted proposal', function () {
    $fixture = stage4ApprovalFixture([
        'status' => OrderProposalStatus::ConvertedToSupplierOrder,
    ], [
        ['status' => OrderProposalItemStatus::Approved, 'approved_quantity' => 156],
    ]);

    app(OrderProposalApprovalService::class)->approveProposal($fixture['proposal'], $fixture['user']);
})->throws(ValidationException::class);
