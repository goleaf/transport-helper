<?php

use App\Services\Supply\Procurement\ProcurementExceptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Support\ProcurementTestSupport;

uses(RefreshDatabase::class);

it('requests approves rejects and does not approve order automatically', function (): void {
    $fixture = ProcurementTestSupport::fixture();
    $service = app(ProcurementExceptionService::class);
    $exception = $service->requestException($fixture['proposal'], 'budget_overrun', 'Temporary approved overrun.', $fixture['user'])['exception'];
    $approved = $service->approve($exception, $fixture['manager'], 'Manager accepted risk.')['exception'];
    $second = $service->requestException($fixture['order'], 'missing_price', 'Need manual price review.', $fixture['user'])['exception'];
    $rejected = $service->reject($second, $fixture['manager'], 'Insufficient reason.')['exception'];

    expect($approved->status->value)->toBe('approved')
        ->and($rejected->status->value)->toBe('rejected')
        ->and($service->hasApprovedException($fixture['proposal'], ['budget_overrun']))->toBeTrue()
        ->and($fixture['proposal']->refresh()->approved_at)->toBeNull();
});

it('requires exception reason', function (): void {
    $fixture = ProcurementTestSupport::fixture();

    expect(fn () => app(ProcurementExceptionService::class)->requestException($fixture['proposal'], 'other', '', $fixture['user']))
        ->toThrow(ValidationException::class);
});
