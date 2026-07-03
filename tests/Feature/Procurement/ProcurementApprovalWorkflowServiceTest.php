<?php

use App\Models\AuditLog;
use App\Services\Supply\Procurement\ProcurementApprovalWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Support\ProcurementTestSupport;

uses(RefreshDatabase::class);

it('requests approval manager approves and self approval is blocked by default', function (): void {
    $fixture = ProcurementTestSupport::fixture();
    $service = app(ProcurementApprovalWorkflowService::class);
    $request = $service->requestApproval($fixture['proposal'], [[
        'type' => 'amount_threshold',
        'amount' => 6000,
        'currency' => 'EUR',
        'required_role' => 'admin',
    ]], $fixture['user'], 'Above threshold.')['request'];

    expect(fn () => $service->approve($request, $fixture['user'], 'Trying self approval.'))->toThrow(ValidationException::class);

    $approved = $service->approve($request->refresh(), $fixture['manager'], 'Approved by manager.')['request'];
    $state = $service->hasSufficientApproval($fixture['proposal'], [['type' => 'amount_threshold']]);

    expect($approved->status->value)->toBe('approved')
        ->and($state['sufficient'])->toBeTrue()
        ->and(AuditLog::query()->where('event_type', 'procurement_approval_approved')->exists())->toBeTrue();
});

it('requires rejection reason', function (): void {
    $fixture = ProcurementTestSupport::fixture();
    $service = app(ProcurementApprovalWorkflowService::class);
    $request = $service->requestApproval($fixture['proposal'], [['type' => 'amount_threshold']], $fixture['user'], 'Need approval.')['request'];

    expect(fn () => $service->reject($request, $fixture['manager'], ''))->toThrow(ValidationException::class);
});
