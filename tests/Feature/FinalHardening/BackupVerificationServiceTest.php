<?php

use App\Services\Supply\Backup\BackupVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('warns when backup marker is missing', function (): void {
    config()->set('supply.backup.marker_path', storage_path('app/backups/missing-marker.txt'));

    $result = app(BackupVerificationService::class)->verify();

    expect($result['status'])->toBe('warning')
        ->and(collect($result['checks'])->firstWhere('name', 'backup_marker')['status'])->toBe('warning');
});

it('passes fresh marker check', function (): void {
    $path = storage_path('app/backups/last_successful_backup.txt');
    if (! is_dir(dirname($path))) {
        mkdir(dirname($path), 0775, true);
    }
    file_put_contents($path, now()->toISOString());
    config()->set('supply.backup.marker_path', $path);

    $result = app(BackupVerificationService::class)->verify();

    expect(collect($result['checks'])->firstWhere('name', 'backup_marker')['status'])->toBe('ok');
});

it('warns when marker is too old', function (): void {
    $path = storage_path('app/backups/old_backup_marker.txt');
    if (! is_dir(dirname($path))) {
        mkdir(dirname($path), 0775, true);
    }
    file_put_contents($path, now()->subHours(72)->toISOString());
    touch($path, now()->subHours(72)->timestamp);
    config()->set('supply.backup.marker_path', $path);
    config()->set('supply.backup.max_age_hours', 48);

    $result = app(BackupVerificationService::class)->verify();

    expect(collect($result['checks'])->firstWhere('name', 'backup_marker_freshness')['status'])->toBe('warning');
});

it('checks required storage directories and backup docs', function (): void {
    $result = app(BackupVerificationService::class)->verify(['ensure_directories' => true]);

    expect(collect($result['checks'])->pluck('name'))->toContain('storage_imports', 'storage_exports', 'backup_plan_doc');
});

it('backup verification command supports json output', function (): void {
    $this->artisan('supply:backup-verify --json')
        ->expectsOutputToContain('"checks"')
        ->assertExitCode(0);
});
