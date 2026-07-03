<?php

use App\Models\AuditLog;
use App\Models\ProcurementApprovalRequest;
use App\Models\ProcurementException;
use App\Models\SupplierOrder;
use App\Services\Supply\Procurement\ProcurementGateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ProcurementTestSupport;

uses(RefreshDatabase::class);

it('advisory gate passes with warnings', function (): void {
    $fixture = ProcurementTestSupport::fixture(['order_unit_price' => null]);

    $result = app(ProcurementGateService::class)->gate($fixture['proposal'], 'approve_order_proposal', $fixture['user']);

    expect($result['status'])->toBe('passed_with_warnings')
        ->and($result['warnings'])->not->toBeEmpty();
});

it('enforced gate blocks without approval and passes with approval and exception', function (): void {
    $fixture = ProcurementTestSupport::fixture([
        'enforcement_mode' => 'enforced',
        'budget_line_amount' => 50,
        'order_unit_price' => null,
        'approval_thresholds_json' => [['scope' => 'company', 'amount' => 1, 'required_role' => 'admin']],
    ]);
    ProcurementTestSupport::price($fixture['company'], $fixture['supplier'], $fixture['product'], 10);
    $service = app(ProcurementGateService::class);

    $options = ['price_map' => [$fixture['product']->id => 10]];

    $blocked = $service->gate($fixture['proposal'], 'approve_order_proposal', $fixture['user'], $options);
    ProcurementApprovalRequest::factory()->for($fixture['company'])->create([
        'approvable_type' => $fixture['proposal']::class,
        'approvable_id' => $fixture['proposal']->id,
        'status' => 'approved',
        'requested_by_user_id' => $fixture['user']->id,
        'reason' => 'Approved threshold.',
    ]);
    ProcurementException::factory()->for($fixture['company'])->create([
        'exceptable_type' => $fixture['proposal']::class,
        'exceptable_id' => $fixture['proposal']->id,
        'exception_type' => 'budget_overrun',
        'status' => 'approved',
        'reason' => 'Approved budget overrun.',
        'requested_by_user_id' => $fixture['user']->id,
        'approved_by_user_id' => $fixture['manager']->id,
        'approved_at' => now(),
    ]);
    $passed = $service->gate($fixture['proposal'], 'approve_order_proposal', $fixture['user'], $options);

    expect($blocked['status'])->toBe('blocked')
        ->and($passed['status'])->toBe('passed_with_warnings')
        ->and(SupplierOrder::query()->count())->toBe(1)
        ->and(AuditLog::query()->where('event_type', 'procurement_gate_checked')->exists())->toBeTrue();
});
