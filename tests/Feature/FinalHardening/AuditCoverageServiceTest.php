<?php

use App\Services\Supply\Security\AuditCoverageService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('expected events include critical workflow events', function (): void {
    $events = app(AuditCoverageService::class)->expectedEvents();

    expect($events)->toContain(
        'calculation_run_completed',
        'supplier_email_sent',
        'ai_extraction_accepted',
        'supplier_confirmation_applied',
        'carrier_selected',
        'goods_receipt_recorded',
        'health_check_run',
    );
});

it('audit coverage service returns a stable structure', function (): void {
    $result = app(AuditCoverageService::class)->run();

    expect($result)->toHaveKeys([
        'status',
        'checks',
        'expected_events',
        'missing_event_references',
        'missing_service_audit_references',
    ]);
});

it('audit coverage command runs and supports json output', function (): void {
    $this->artisan('supply:audit-coverage --json')
        ->expectsOutputToContain('"expected_events"')
        ->assertExitCode(0);
});

it('does not throw when optional event references are absent', function (): void {
    $result = app(AuditCoverageService::class)->run();

    expect($result['checks'])->toBeArray()
        ->and($result['status'])->toBeIn(['ok', 'warning', 'error']);
});
