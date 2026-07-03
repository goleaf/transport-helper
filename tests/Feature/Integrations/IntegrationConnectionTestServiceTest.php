<?php

use App\Models\AuditLog;
use App\Models\IntegrationConnection;
use App\Models\User;
use App\Services\Supply\Integrations\IntegrationConnectionTestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('returns success for manual provider dry run', function (): void {
    $connection = IntegrationConnection::factory()->create([
        'provider' => 'manual',
        'is_external' => false,
        'requires_approval' => false,
        'encrypted_config' => ['mode' => 'manual'],
    ]);

    $result = app(IntegrationConnectionTestService::class)->test($connection, [], User::factory()->create(['role' => 'admin']));

    expect($result['status'])->toBe('success')
        ->and($connection->fresh()->last_test_status)->toBe('success');
});

it('dry-runs gmail without external calls', function (): void {
    $connection = IntegrationConnection::factory()->create([
        'provider' => 'gmail',
        'is_external' => true,
        'requires_approval' => true,
        'approval_status' => 'approved',
        'encrypted_config' => ['client_secret' => 'secret-value', 'refresh_token' => 'refresh-value'],
    ]);

    $result = app(IntegrationConnectionTestService::class)->test($connection, ['dry_run' => true], User::factory()->create(['role' => 'admin']));

    expect($result['status'])->toBe('warning')
        ->and($result['warnings'])->toContain('real_call_not_performed')
        ->and(json_encode($connection->fresh()->last_test_result_json))->not->toContain('secret-value')
        ->and(json_encode($connection->fresh()->last_test_result_json))->not->toContain('refresh-value');
});

it('blocks real calls without approval', function (): void {
    $connection = IntegrationConnection::factory()->create([
        'provider' => 'imap',
        'is_external' => true,
        'approval_status' => 'pending',
        'encrypted_config' => ['password' => 'secret'],
    ]);

    expect(fn () => app(IntegrationConnectionTestService::class)->test($connection, [
        'dry_run' => false,
        'allow_real_call' => true,
    ], User::factory()->create(['role' => 'admin'])))->toThrow(ValidationException::class);
});

it('blocks real calls in testing environment even when approved', function (): void {
    config(['supply.integrations.real_calls_enabled' => true]);
    $connection = IntegrationConnection::factory()->create([
        'provider' => 'smtp',
        'is_external' => true,
        'approval_status' => 'approved',
        'last_test_status' => 'success',
        'encrypted_config' => ['password' => 'secret'],
    ]);

    expect(fn () => app(IntegrationConnectionTestService::class)->test($connection, [
        'dry_run' => false,
        'allow_real_call' => true,
    ], User::factory()->create(['role' => 'admin'])))->toThrow(ValidationException::class);
});

it('audits test results without leaking secrets', function (): void {
    $connection = IntegrationConnection::factory()->create([
        'provider' => 'google_sheets',
        'is_external' => true,
        'approval_status' => 'approved',
        'encrypted_config' => ['client_secret' => 'secret-for-audit'],
    ]);

    app(IntegrationConnectionTestService::class)->test($connection, ['dry_run' => true], User::factory()->create(['role' => 'admin']));

    $audit = AuditLog::query()->where('event_type', 'integration_connection_tested')->firstOrFail();

    expect(json_encode($audit->metadata_json))->not->toContain('secret-for-audit');
});
