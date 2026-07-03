<?php

use App\Models\AuditLog;
use App\Models\EmailMessage;
use App\Services\Supply\Transport\CarrierQuoteRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\TransportTestSupport;

uses(RefreshDatabase::class);

it('prepares quote request text and creates outbound drafts without sending', function () {
    $fixture = TransportTestSupport::fixture();

    $result = app(CarrierQuoteRequestService::class)->prepareRequests($fixture['supplierOrder'], [
        $fixture['carrier']->id,
    ], [
        'pickup_location' => 'Kaunas',
        'delivery_location' => 'Vilnius',
        'requested_delivery_date' => '2026-07-20',
        'create_email_drafts' => true,
    ], $fixture['user']);

    expect($result['drafts'])->toHaveCount(1)
        ->and($result['drafts'][0]['body'])->toContain('Kaunas')
        ->and(EmailMessage::query()->where('direction', 'outbound')->where('status', 'draft')->exists())->toBeTrue()
        ->and(EmailMessage::query()->whereNotNull('sent_at')->exists())->toBeFalse()
        ->and(AuditLog::query()->where('event_type', 'carrier_quote_requests_prepared')->exists())->toBeTrue();
});

it('returns warning when carrier has no contacts', function () {
    $fixture = TransportTestSupport::fixture();
    $fixture['carrier']->contacts()->delete();

    $result = app(CarrierQuoteRequestService::class)->prepareRequests($fixture['supplierOrder'], [
        $fixture['carrier']->id,
    ], ['create_email_drafts' => false], $fixture['user']);

    expect($result['warnings'][0])->toContain('missing_carrier_contacts');
});
