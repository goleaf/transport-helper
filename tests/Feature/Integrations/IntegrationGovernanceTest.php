<?php

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\IntegrationConnection;
use App\Models\User;
use App\Services\Supply\Integrations\IntegrationApprovalService;
use App\Services\Supply\Integrations\IntegrationConfigService;
use App\Services\Supply\Integrations\IntegrationCredentialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('creates integration connections with encrypted config and masked output', function (): void {
    $company = Company::factory()->create();
    $admin = User::factory()->create(['role' => 'admin']);

    $result = app(IntegrationConfigService::class)->createConnection([
        'company_id' => $company->id,
        'type' => 'email',
        'provider' => 'gmail',
        'name' => 'Procurement Gmail',
        'environment' => 'production',
        'config' => [
            'client_id' => 'client-example',
            'client_secret' => 'super-secret-value',
            'refresh_token' => 'refresh-token-value',
        ],
        'is_external' => true,
        'requires_approval' => true,
    ], $admin);

    $connection = $result['connection'];
    $raw = DB::table('integration_connections')->where('id', $connection->id)->value('encrypted_config');

    expect($connection)->toBeInstanceOf(IntegrationConnection::class)
        ->and($connection->status)->toBe('configured')
        ->and($connection->approval_status)->toBe('pending')
        ->and($connection->is_active)->toBeFalse()
        ->and($raw)->not->toContain('super-secret-value')
        ->and($raw)->not->toContain('refresh-token-value')
        ->and($result['masked_config']['client_secret'])->toBe('********')
        ->and($result['masked_config']['refresh_token'])->toBe('********');
});

it('masks nested credential values without exposing secrets', function (): void {
    $masked = app(IntegrationCredentialService::class)->maskConfig([
        'api_key' => 'abc123456789',
        'nested' => [
            'password' => 'top-secret',
            'label' => 'Inbox',
        ],
    ]);

    expect($masked['api_key'])->toBe('********6789')
        ->and($masked['nested']['password'])->toBe('********')
        ->and($masked['nested']['label'])->toBe('Inbox');
});

it('requires approval and successful test before activation', function (): void {
    $company = Company::factory()->create();
    $admin = User::factory()->create(['role' => 'admin']);
    $connection = IntegrationConnection::factory()->for($company)->create([
        'provider' => 'gmail',
        'is_external' => true,
        'requires_approval' => true,
        'status' => 'configured',
        'approval_status' => 'pending',
        'is_active' => false,
        'last_test_status' => null,
    ]);

    expect(fn () => app(IntegrationApprovalService::class)->activate($connection, $admin))
        ->toThrow(ValidationException::class);

    app(IntegrationApprovalService::class)->approve($connection, $admin, 'Approved for mailbox dry run.');

    expect(fn () => app(IntegrationApprovalService::class)->activate($connection->fresh(), $admin))
        ->toThrow(ValidationException::class);

    $connection->forceFill(['last_test_status' => 'success'])->save();
    $result = app(IntegrationApprovalService::class)->activate($connection->fresh(), $admin);

    expect($result['connection']->status)->toBe('active')
        ->and($result['connection']->is_active)->toBeTrue();
});

it('blocks non-admin integration approval', function (): void {
    $connection = IntegrationConnection::factory()->create([
        'provider' => 'smtp',
        'is_external' => true,
        'approval_status' => 'pending',
        'status' => 'pending_approval',
    ]);
    $viewer = User::factory()->create(['role' => 'viewer']);

    expect(fn () => app(IntegrationApprovalService::class)->approve($connection, $viewer, 'Nope'))
        ->toThrow(ValidationException::class);
});

it('writes audit logs for integration approval actions without secrets', function (): void {
    $admin = User::factory()->create(['role' => 'admin']);
    $connection = IntegrationConnection::factory()->create([
        'provider' => 'imap',
        'encrypted_config' => ['password' => 'secret-password'],
        'status' => 'configured',
        'approval_status' => 'pending',
    ]);

    app(IntegrationApprovalService::class)->submitForApproval($connection, $admin, 'Ready to review.');
    app(IntegrationApprovalService::class)->approve($connection->fresh(), $admin, 'Approved.');

    $events = AuditLog::query()->pluck('event_type')->all();
    $metadata = AuditLog::query()->pluck('metadata_json')->map(fn ($value) => json_encode($value))->implode("\n");

    expect($events)->toContain('integration_submitted_for_approval', 'integration_approved')
        ->and($metadata)->not->toContain('secret-password');
});
