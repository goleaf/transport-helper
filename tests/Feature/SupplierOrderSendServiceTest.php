<?php

use App\Enums\LogisticsStatus;
use App\Enums\SupplierOrderStatus;
use App\Exceptions\NotConfiguredYetException;
use App\Models\AuditLog;
use App\Models\EmailMessage;
use App\Services\Supply\SupplierOrders\SupplierOrderEmailApprovalService;
use App\Services\Supply\SupplierOrders\SupplierOrderEmailDraftService;
use App\Services\Supply\SupplierOrders\SupplierOrderSendService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

require_once __DIR__.'/SupplierOrderStage5Support.php';

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake(config('filesystems.default'));
});

function stage5ApprovedSupplierOrderEmailFixture(bool $withAttachment = true): array
{
    $fixture = stage5SupplierOrderFixture();
    app(SupplierOrderEmailDraftService::class)->prepareDraft(
        $fixture['order'],
        ['auto_export' => $withAttachment],
        $fixture['user'],
    );
    app(SupplierOrderEmailApprovalService::class)->approveEmail(
        $fixture['order']->fresh(),
        ['confirm_no_attachment' => ! $withAttachment],
        $fixture['user'],
    );

    return $fixture;
}

it('sends approved email with log sender', function () {
    $fixture = stage5ApprovedSupplierOrderEmailFixture();

    $result = app(SupplierOrderSendService::class)
        ->send($fixture['order']->fresh(), ['sender' => 'log'], $fixture['user']);

    expect($result['email_message']->status)->toBe('sent')
        ->and($result['email_message']->message_id)->toStartWith('log-')
        ->and($result['supplier_order']->status)->toBe(SupplierOrderStatus::Sent)
        ->and($result['supplier_order']->sent_by_user_id)->toBe($fixture['user']->id)
        ->and($result['supplier_order']->sent_at)->not->toBeNull()
        ->and(AuditLog::query()->where('event_type', 'supplier_email_sent')->exists())->toBeTrue();
});

it('cannot send unapproved email', function () {
    $fixture = stage5SupplierOrderFixture();
    app(SupplierOrderEmailDraftService::class)->prepareDraft($fixture['order'], [], $fixture['user']);

    app(SupplierOrderSendService::class)->send($fixture['order']->fresh(), ['sender' => 'log'], $fixture['user']);
})->throws(ValidationException::class);

it('cannot send twice by default', function () {
    $fixture = stage5ApprovedSupplierOrderEmailFixture();
    $service = app(SupplierOrderSendService::class);

    $service->send($fixture['order']->fresh(), ['sender' => 'log'], $fixture['user']);
    $service->send($fixture['order']->fresh(), ['sender' => 'log'], $fixture['user']);
})->throws(ValidationException::class);

it('updates logistics record to order sent', function () {
    $fixture = stage5ApprovedSupplierOrderEmailFixture();

    app(SupplierOrderSendService::class)
        ->send($fixture['order']->fresh(), ['sender' => 'log'], $fixture['user']);

    expect($fixture['logisticsRecord']->fresh()->status)->toBe(LogisticsStatus::OrderSent)
        ->and(AuditLog::query()->where('event_type', 'logistics_record_status_changed')->exists())->toBeTrue();
});

it('sends without attachment when approval confirmed it', function () {
    $fixture = stage5ApprovedSupplierOrderEmailFixture(false);

    $result = app(SupplierOrderSendService::class)
        ->send($fixture['order']->fresh(), ['sender' => 'log'], $fixture['user']);

    expect($result['email_message']->status)->toBe('sent');
});

it('throws for SMTP placeholder', function () {
    $fixture = stage5ApprovedSupplierOrderEmailFixture();

    app(SupplierOrderSendService::class)
        ->send($fixture['order']->fresh(), ['sender' => 'smtp'], $fixture['user']);
})->throws(NotConfiguredYetException::class);

it('does not call real mail transport when using log sender', function () {
    Mail::fake();
    $fixture = stage5ApprovedSupplierOrderEmailFixture();

    app(SupplierOrderSendService::class)
        ->send($fixture['order']->fresh(), ['sender' => 'log'], $fixture['user']);

    Mail::assertNothingSent();
    expect(EmailMessage::query()->where('status', 'sent')->count())->toBe(1);
});
