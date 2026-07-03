<?php

use App\Models\ExportFile;
use App\Models\OperationalIncident;
use App\Models\User;
use App\Services\Supply\Incidents\IncidentExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('exports csv and json without secrets', function (): void {
    Storage::fake('local');
    OperationalIncident::factory()->create([
        'title' => 'Safe incident',
        'metadata_json' => ['api_token' => 'secret-value'],
    ]);
    $user = User::factory()->create(['role' => 'admin']);
    $service = app(IncidentExportService::class);

    $csv = $service->exportCsv([], $user);
    $json = $service->exportJson([], $user);

    expect(ExportFile::query()->count())->toBe(2)
        ->and(Storage::disk('local')->exists($csv['export_file']->stored_path))->toBeTrue()
        ->and(Storage::disk('local')->exists($json['export_file']->stored_path))->toBeTrue()
        ->and(Storage::disk('local')->get($json['export_file']->stored_path))->not->toContain('secret-value');
});
