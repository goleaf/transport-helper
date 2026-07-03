<?php

use App\Services\Supply\Procurement\ProcurementPolicyResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ProcurementTestSupport;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('resolves default company policy with rules and thresholds', function (): void {
    $fixture = ProcurementTestSupport::fixture([
        'approval_thresholds_json' => [['scope' => 'company', 'amount' => 5000]],
        'supplier_rules_json' => ['minimum_order_value' => 100],
    ]);

    $result = app(ProcurementPolicyResolver::class)->resolve($fixture['company'], $fixture['supplier']);

    expect($result['policy']->is($fixture['policy']))->toBeTrue()
        ->and($result['approval_thresholds'])->toHaveCount(1)
        ->and($result['supplier_rules']['minimum_order_value'])->toBe(100);
});

it('returns advisory default when no policy exists', function (): void {
    $fixture = ProcurementTestSupport::fixture();
    $fixture['policy']->delete();

    $result = app(ProcurementPolicyResolver::class)->resolve($fixture['company']);

    expect($result['policy'])->toBeNull()
        ->and($result['enforcement_mode'])->toBe('advisory')
        ->and($result['warnings'])->toContain('no_procurement_policy');
});
