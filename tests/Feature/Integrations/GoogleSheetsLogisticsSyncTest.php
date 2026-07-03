<?php

use App\Models\AuditLog;
use App\Models\IntegrationConnection;
use App\Services\Audit\AuditLogService;
use App\Services\Integrations\GoogleSheets\FakeGoogleSheetsClient;
use App\Services\Supply\Logistics\LogisticsGoogleSheetsSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Support\LogisticsTestSupport;

uses(RefreshDatabase::class);

it('dry-runs logistics rows without calling google api', function (): void {
    $fixture = LogisticsTestSupport::fixture();

    $result = app(LogisticsGoogleSheetsSyncService::class)->sync([], $fixture['user']);

    expect($result['dry_run'])->toBeTrue()
        ->and($result['row_count'])->toBeGreaterThanOrEqual(1)
        ->and($result['provider_result'])->toBeNull();
});

it('blocks real sync without approved integration', function (): void {
    $fixture = LogisticsTestSupport::fixture();

    expect(fn () => app(LogisticsGoogleSheetsSyncService::class)->sync([], $fixture['user'], [
        'dry_run' => false,
        'allow_real_call' => true,
    ]))->toThrow(ValidationException::class);
});

it('syncs through fake google sheets client when explicitly allowed and approved', function (): void {
    $fixture = LogisticsTestSupport::fixture();
    $integration = IntegrationConnection::factory()->for($fixture['company'])->create([
        'type' => 'google_sheets',
        'provider' => 'google_sheets',
        'is_external' => true,
        'requires_approval' => true,
        'approval_status' => 'approved',
        'status' => 'active',
        'is_active' => true,
        'last_test_status' => 'success',
        'encrypted_config' => ['spreadsheet_id' => 'sheet-123', 'client_secret' => 'secret'],
    ]);

    $service = new LogisticsGoogleSheetsSyncService(new FakeGoogleSheetsClient, app(AuditLogService::class));
    $result = $service->sync([], $fixture['user'], [
        'dry_run' => false,
        'allow_real_call' => true,
        'connection_id' => $integration->id,
        'allow_testing_real_call_with_fake_client' => true,
    ]);

    expect($result['dry_run'])->toBeFalse()
        ->and($result['provider_result']['written_rows'])->toBeGreaterThanOrEqual(1);
});

it('audits google sheets dry-run and sync attempts without secrets', function (): void {
    $fixture = LogisticsTestSupport::fixture();

    app(LogisticsGoogleSheetsSyncService::class)->sync([], $fixture['user']);

    $audit = AuditLog::query()->where('event_type', 'google_sheets_logistics_sync_dry_run')->firstOrFail();

    expect(json_encode($audit->metadata_json))->not->toContain('secret');
});

it('test command can dry-run an integration connection', function (): void {
    $connection = IntegrationConnection::factory()->create(['provider' => 'google_sheets']);

    $this->artisan('supply:test-integration', [
        'connection_id' => $connection->id,
        '--dry-run' => true,
    ])->assertExitCode(0);
});
