<?php

use App\Models\AuditLog;
use App\Models\PilotSupplier;
use App\Models\User;
use App\Services\Supply\Pilot\PilotApprovalService;
use App\Services\Supply\Pilot\PilotDryRunService;
use App\Services\Supply\Pilot\PilotUatChecklistService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('pilot code does not introduce dto classes or app data directory', function (): void {
    expect(is_dir(app_path('Data')))->toBeFalse();

    $dtoFiles = collect(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path())))
        ->filter(fn (SplFileInfo $file): bool => $file->isFile())
        ->filter(fn (SplFileInfo $file): bool => preg_match('/(?:DTO|Dto)\.php$/', $file->getFilename()) === 1)
        ->map(fn (SplFileInfo $file): string => $file->getPathname())
        ->values()
        ->all();

    expect($dtoFiles)->toBe([]);
});

it('pilot dry-runs do not call real email external api ai or auto select carrier', function (): void {
    $pilot = PilotSupplier::factory()->create();
    $user = User::factory()->create(['role' => 'admin']);
    $result = app(PilotDryRunService::class)->runFullUatDryRun($pilot, $user)['result'];

    expect($result['real_email_sent'])->toBeFalse()
        ->and($result['external_api_called'])->toBeFalse()
        ->and($result['external_ai_called'])->toBeFalse()
        ->and($result['carrier_auto_selected'])->toBeFalse()
        ->and($result['integrations_activated'])->toBeFalse();
});

it('pilot approval does not activate integrations and audit has no secrets', function (): void {
    $pilot = PilotSupplier::factory()->create();
    $admin = User::factory()->create(['role' => 'admin']);
    $checklist = app(PilotUatChecklistService::class);
    $items = collect($checklist->defaultChecklist())->map(fn (array $item): array => [
        'key' => $item['key'],
        'status' => 'passed',
        'note' => 'Passed without secret token.',
    ])->all();
    $checklist->updateChecklist($pilot, $items, $admin);

    app(PilotApprovalService::class)->approveForLive($pilot->fresh(), $admin, 'Owner approved pilot.');

    $audit = AuditLog::query()->pluck('metadata_json')->map(fn ($value) => json_encode($value))->implode("\n");

    expect($pilot->fresh()->status)->toBe('approved_for_live')
        ->and($audit)->not->toContain('password'.'=')
        ->and($audit)->not->toContain('api_key');
});
