<?php

use App\Services\AI\Redaction\AiInputRedactionService;
use Tests\TestCase;

uses(TestCase::class);

it('redacts emails and phone numbers while preserving structure', function (): void {
    $result = app(AiInputRedactionService::class)->redact([
        'email' => [
            'from' => 'buyer@example.com',
            'body_text' => 'Call +370 600 12345 or email ops@example.com.',
        ],
    ]);

    expect($result['redacted_input']['email']['from'])->toBe('[EMAIL]')
        ->and($result['redacted_input']['email']['body_text'])->toContain('[PHONE]')
        ->and($result['redacted_input']['email']['body_text'])->toContain('[EMAIL]')
        ->and($result['redactions'])->not->toBeEmpty();
});

it('redacts token secret password and api key values', function (): void {
    $result = app(AiInputRedactionService::class)->redact([
        'config' => [
            'token' => 'tok_abc',
            'client_secret' => 'secret',
            'password' => 'password',
            'api_key' => 'key',
            'label' => 'safe',
        ],
    ]);

    expect($result['redacted_input']['config']['token'])->toBe('[SECRET]')
        ->and($result['redacted_input']['config']['client_secret'])->toBe('[SECRET]')
        ->and($result['redacted_input']['config']['password'])->toBe('[SECRET]')
        ->and($result['redacted_input']['config']['api_key'])->toBe('[SECRET]')
        ->and($result['redacted_input']['config']['label'])->toBe('safe');
});

it('redacts configured customer and project names and prices', function (): void {
    $result = app(AiInputRedactionService::class)->redact([
        'body' => 'Customer Acme Corp needs Project Sunrise at price 1234.56 EUR.',
    ], [
        'customer_names' => ['Acme Corp'],
        'project_names' => ['Project Sunrise'],
        'redact_prices' => true,
    ]);

    expect($result['redacted_input']['body'])->toContain('[CUSTOMER]')
        ->and($result['redacted_input']['body'])->toContain('[PROJECT]')
        ->and($result['redacted_input']['body'])->toContain('[PRICE]')
        ->and($result['redacted_input']['body'])->not->toContain('Acme Corp')
        ->and($result['redacted_input']['body'])->not->toContain('1234.56');
});
