<?php

use App\Models\PilotSupplier;
use App\Models\User;
use App\Services\Supply\Pilot\PilotUatChecklistService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('default checklist contains required sections and blocks live until critical pass', function (): void {
    $service = app(PilotUatChecklistService::class);
    $pilot = PilotSupplier::factory()->create();

    $sections = collect($service->defaultChecklist())->pluck('section')->unique()->values()->all();
    $evaluation = $service->evaluate($pilot);

    expect($sections)->toContain('data_import', 'calculation', 'transport', 'security_operations')
        ->and($evaluation['live_ready'])->toBeFalse();
});

it('marks items and allows live when all critical items pass', function (): void {
    $service = app(PilotUatChecklistService::class);
    $pilot = PilotSupplier::factory()->create();
    $user = User::factory()->create(['role' => 'admin']);
    $items = collect($service->defaultChecklist())
        ->map(fn (array $item): array => ['key' => $item['key'], 'status' => 'passed', 'note' => 'Passed', 'evidence' => 'Tested'])
        ->all();

    $result = $service->updateChecklist($pilot, $items, $user);

    expect($result['evaluation']['live_ready'])->toBeTrue();
});
