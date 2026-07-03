<?php

use App\Enums\OrderProposalItemStatus;
use App\Enums\OrderProposalStatus;
use App\Models\CalculationRun;
use App\Models\Company;
use App\Models\OrderProposal;
use App\Models\OrderProposalItem;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\Supply\OrderProposals\OrderProposalSummaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function stage4SummaryProposal(): OrderProposal
{
    $company = Company::factory()->create();
    $supplier = Supplier::factory()->for($company)->create();
    $run = CalculationRun::factory()->for($company)->for($supplier)->create();
    $proposal = OrderProposal::factory()
        ->for($company)
        ->for($supplier)
        ->for($run, 'calculationRun')
        ->create([
            'status' => OrderProposalStatus::Draft,
            'total_lines' => 5,
        ]);

    $product = Product::factory()->for($company)->create();

    foreach ([
        [OrderProposalItemStatus::Draft, null, 10],
        [OrderProposalItemStatus::NeedsReview, null, 20],
        [OrderProposalItemStatus::Approved, 30, 30],
        [OrderProposalItemStatus::Adjusted, 40, 50],
        [OrderProposalItemStatus::Rejected, null, 60],
    ] as [$status, $approvedQuantity, $recommendedQuantity]) {
        OrderProposalItem::factory()
            ->for($proposal, 'orderProposal')
            ->for($product)
            ->create([
                'status' => $status,
                'approved_quantity' => $approvedQuantity,
                'recommended_quantity' => $recommendedQuantity,
            ]);
    }

    return $proposal;
}

it('summarizes proposal item counts and quantities', function () {
    $summary = app(OrderProposalSummaryService::class)->summarize(stage4SummaryProposal());

    expect($summary['total_lines'])->toBe(5)
        ->and($summary['draft_count'])->toBe(1)
        ->and($summary['needs_review_count'])->toBe(1)
        ->and($summary['approved_count'])->toBe(1)
        ->and($summary['adjusted_count'])->toBe(1)
        ->and($summary['rejected_count'])->toBe(1)
        ->and($summary['resolved_count'])->toBe(3)
        ->and($summary['unresolved_count'])->toBe(2)
        ->and($summary['orderable_count'])->toBe(2)
        ->and($summary['total_recommended_quantity'])->toBe(170.0)
        ->and($summary['total_approved_quantity'])->toBe(70.0)
        ->and($summary['can_approve'])->toBeFalse()
        ->and($summary['blocking_reasons'])->toContain('unresolved_items_exist');
});
