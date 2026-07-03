<?php

use App\Services\Supply\Security\AiBoundaryAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('calculation engine forbidden dependencies are not found', function (): void {
    $result = app(AiBoundaryAuditService::class)->run();

    expect(collect($result['checks'])->firstWhere('name', 'calculation_engine_boundary')['status'])->toBe('ok');
});

it('form autofill direct business mutation is not found', function (): void {
    $result = app(AiBoundaryAuditService::class)->run();

    expect(collect($result['checks'])->firstWhere('name', 'form_autofill_boundary')['status'])->toBe('ok');
});

it('carrier scoring and comparison do not select carrier', function (): void {
    $result = app(AiBoundaryAuditService::class)->run();

    expect(collect($result['checks'])->firstWhere('name', 'carrier_scoring_selection_boundary')['status'])->toBe('ok');
});

it('ai boundary audit command runs with json output', function (): void {
    $this->artisan('supply:ai-boundary-audit --json')
        ->expectsOutputToContain('"checks"')
        ->assertExitCode(0);
});
