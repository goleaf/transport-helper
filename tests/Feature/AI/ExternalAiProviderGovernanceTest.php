<?php

use App\Models\AuditLog;
use App\Models\IntegrationConnection;
use App\Services\AI\Providers\LocalLlmProvider;
use App\Services\AI\Providers\RedactedExternalAiProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('keeps external ai disabled by default', function (): void {
    config(['supply.external_ai.enabled' => false]);

    expect(fn () => app(RedactedExternalAiProvider::class)->suggest([
        'body' => 'Please extract this.',
    ]))->toThrow(ValidationException::class);
});

it('requires approved external ai integration before use', function (): void {
    config(['supply.external_ai.enabled' => true]);
    IntegrationConnection::factory()->create([
        'provider' => 'external_ai',
        'is_external' => true,
        'approval_status' => 'pending',
        'status' => 'configured',
        'is_active' => false,
    ]);

    expect(fn () => app(RedactedExternalAiProvider::class)->suggest([
        'body' => 'Supplier private note test@example.com',
    ]))->toThrow(ValidationException::class);
});

it('uses redaction before placeholder external ai call', function (): void {
    config(['supply.external_ai.enabled' => true]);
    IntegrationConnection::factory()->create([
        'provider' => 'external_ai',
        'is_external' => true,
        'approval_status' => 'approved',
        'status' => 'active',
        'is_active' => true,
        'last_test_status' => 'success',
        'encrypted_config' => ['api_key' => 'secret'],
    ]);

    $result = app(RedactedExternalAiProvider::class)->suggest([
        'body' => 'Contact buyer@example.com about Project Sunrise.',
    ], [
        'project_names' => ['Project Sunrise'],
    ]);

    expect($result['redacted_input']['body'])->toContain('[EMAIL]')
        ->and($result['redacted_input']['body'])->toContain('[PROJECT]')
        ->and($result['provider'])->toBe('external_ai_placeholder')
        ->and(json_encode(AuditLog::query()->pluck('metadata_json')->all()))->not->toContain('secret');
});

it('local llm provider is extraction-only and does not mutate business records', function (): void {
    $result = app(LocalLlmProvider::class)->suggest([
        'body' => 'Extract order number PO-1001 only.',
    ]);

    expect($result['provider'])->toBe('local_llm')
        ->and($result['mutates_business_records'])->toBeFalse();
});
