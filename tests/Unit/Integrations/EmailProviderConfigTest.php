<?php

use App\Services\Email\Providers\GmailEmailProvider;
use App\Services\Email\Providers\ImapEmailProvider;
use App\Services\Email\Providers\MicrosoftGraphEmailProvider;
use App\Services\Email\Senders\SmtpEmailSender;
use Tests\TestCase;

uses(TestCase::class);

it('validates gmail provider config without making a real call', function (): void {
    $result = app(GmailEmailProvider::class)->validateConfig([
        'client_id' => 'client-id',
        'client_secret' => 'secret',
        'refresh_token' => 'refresh',
        'label' => 'INBOX',
    ]);

    expect($result['valid'])->toBeTrue()
        ->and($result['real_call_performed'])->toBeFalse();
});

it('validates microsoft graph provider config', function (): void {
    $result = app(MicrosoftGraphEmailProvider::class)->validateConfig([
        'tenant_id' => 'tenant',
        'client_id' => 'client',
        'client_secret' => 'secret',
        'mailbox' => 'procurement@example.test',
    ]);

    expect($result['valid'])->toBeTrue()
        ->and($result['missing'])->toBe([]);
});

it('validates imap provider config', function (): void {
    $result = app(ImapEmailProvider::class)->validateConfig([
        'host' => 'imap.example.test',
        'port' => 993,
        'encryption' => 'ssl',
        'username' => 'user',
        'password' => 'secret',
        'mailbox' => 'INBOX',
    ]);

    expect($result['valid'])->toBeTrue();
});

it('validates smtp sender config', function (): void {
    $result = app(SmtpEmailSender::class)->validateConfig([
        'host' => 'smtp.example.test',
        'port' => 587,
        'encryption' => 'tls',
        'username' => 'user',
        'password' => 'secret',
        'from_email' => 'orders@example.test',
    ]);

    expect($result['valid'])->toBeTrue();
});

it('reports missing credentials cleanly', function (): void {
    $result = app(GmailEmailProvider::class)->validateConfig([
        'client_id' => 'client-id',
    ]);

    expect($result['valid'])->toBeFalse()
        ->and($result['missing'])->toContain('client_secret', 'refresh_token')
        ->and(json_encode($result))->not->toContain('secret-value');
});
