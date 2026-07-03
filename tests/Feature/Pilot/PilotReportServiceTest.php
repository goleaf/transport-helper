<?php

use App\Models\AuditLog;
use App\Models\ExportFile;
use App\Models\PilotSupplier;
use App\Models\User;
use App\Services\Supply\Pilot\PilotReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('generates and exports pilot readiness and uat reports', function (): void {
    Storage::fake('local');
    $pilot = PilotSupplier::factory()->create(['readiness_result_json' => ['status' => 'passed']]);
    $user = User::factory()->create(['role' => 'admin']);
    $service = app(PilotReportService::class);

    $readiness = $service->generateReadinessReport($pilot);
    $export = $service->exportReportJson($pilot, 'uat', $user);

    expect($readiness['report_type'])->toBe('readiness')
        ->and($export['export_file'])->toBeInstanceOf(ExportFile::class)
        ->and(Storage::disk('local')->exists($export['export_file']->stored_path))->toBeTrue()
        ->and(AuditLog::query()->where('event_type', 'pilot_report_exported')->exists())->toBeTrue();
});
