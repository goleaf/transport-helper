<?php

use App\Services\Supply\Procurement\ApprovalRequirementService;
use Tests\TestCase;

uses(TestCase::class);

it('requires approval for amount thresholds, budget overrun and missing price', function (): void {
    $policy = [
        'approval_thresholds' => [['scope' => 'company', 'amount' => 5000, 'required_role' => 'admin']],
        'rules' => ['missing_price_requires_approval' => true],
    ];
    $estimation = ['total' => 6000, 'currency' => 'EUR', 'missing_price_count' => 1];
    $budget = ['over_budget_amount' => 100, 'estimated_amount' => 6000, 'available_amount' => 5900, 'currency' => 'EUR'];

    $result = app(ApprovalRequirementService::class)->determine($policy, $estimation, $budget);

    expect($result['requires_approval'])->toBeTrue()
        ->and(collect($result['requirements'])->pluck('type')->all())->toContain('amount_threshold', 'budget_overrun', 'missing_price');
});

it('does not require approval under threshold', function (): void {
    $result = app(ApprovalRequirementService::class)->determine(
        ['approval_thresholds' => [['scope' => 'company', 'amount' => 5000]]],
        ['total' => 100, 'currency' => 'EUR', 'missing_price_count' => 0],
        ['over_budget_amount' => 0],
    );

    expect($result['requires_approval'])->toBeFalse();
});

it('matches supplier specific threshold', function (): void {
    $result = app(ApprovalRequirementService::class)->determine(
        ['approval_thresholds' => [['scope' => 'supplier', 'supplier_id' => 7, 'amount' => 10]]],
        ['total' => 20, 'currency' => 'EUR', 'missing_price_count' => 0],
        ['over_budget_amount' => 0],
        ['supplier_id' => 7],
    );

    expect($result['requirements'][0]['type'])->toBe('amount_threshold');
});
