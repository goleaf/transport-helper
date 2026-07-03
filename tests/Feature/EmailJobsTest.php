<?php

require_once __DIR__.'/InboundEmailStage6Support.php';

use App\Jobs\AnalyzeInboundEmailJob;
use App\Jobs\FetchEmailMessagesJob;
use App\Models\AiEmailExtraction;
use App\Models\EmailMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('fetch email messages job ingests manual messages', function (): void {
    $fixture = inboundEmailStage6Fixture();

    dispatch_sync(new FetchEmailMessagesJob($fixture['company']->getKey(), $fixture['emailAccount']->getKey(), 'manual', [
        'messages' => [[
            'message_id' => 'job-fetch-stage6',
            'from_email' => 'orders@acme.test',
            'subject' => 'PO-20260703-1',
            'received_at' => '2026-07-03 12:00:00',
        ]],
    ]));

    expect(EmailMessage::query()->where('message_id', 'job-fetch-stage6')->exists())->toBeTrue();
});

it('analyze inbound email job creates extraction', function (): void {
    $fixture = inboundEmailStage6Fixture();
    $email = inboundEmailStage6Message($fixture);

    dispatch_sync(new AnalyzeInboundEmailJob($email->getKey(), [
        'analyzer' => 'fake',
        'fake_output' => inboundEmailStage6Output(),
    ]));

    expect(AiEmailExtraction::query()->count())->toBe(1);
});

it('jobs do not call external services', function (): void {
    $sources = collect([
        app_path('Jobs/FetchEmailMessagesJob.php'),
        app_path('Jobs/AnalyzeInboundEmailJob.php'),
    ])->map(fn (string $path): string => file_get_contents($path) ?: '')->implode("\n");

    expect($sources)->not->toContain('OpenAI')
        ->and($sources)->not->toContain('Guzzle')
        ->and($sources)->not->toContain('Http::')
        ->and($sources)->not->toContain('imap_open');
});
