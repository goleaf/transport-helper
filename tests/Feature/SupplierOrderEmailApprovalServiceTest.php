<?php

use App\Enums\SupplierOrderStatus;
use App\Models\AuditLog;
use App\Models\EmailMessage;
use App\Services\Supply\SupplierOrders\SupplierOrderEmailApprovalService;
use App\Services\Supply\SupplierOrders\SupplierOrderEmailDraftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

require_once __DIR__.'/SupplierOrderStage5Support.php';

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake(config('filesystems.default'));
});

it('marks order and email approved', function () {
    $fixture = stage5SupplierOrderFixture();
    app(SupplierOrderEmailDraftService::class)->prepareDraft($fixture['order'], [], $fixture['user']);

    $result = app(SupplierOrderEmailApprovalService::class)
        ->approveEmail($fixture['order']->fresh(), [], $fixture['user']);

    expect($result['supplier_order']->status)->toBe(SupplierOrderStatus::Approved)
        ->and($result['supplier_order']->email_approved_at)->not->toBeNull()
        ->and($result['supplier_order']->email_approved_by_user_id)->toBe($fixture['user']->id)
        ->and($result['email_message']->status)->toBe('approved')
        ->and(AuditLog::query()->where('event_type', 'supplier_email_approved')->exists())->toBeTrue();
});

it('requires attachment or confirmation', function () {
    $fixture = stage5SupplierOrderFixture();
    app(SupplierOrderEmailDraftService::class)
        ->prepareDraft($fixture['order'], ['auto_export' => false], $fixture['user']);

    app(SupplierOrderEmailApprovalService::class)
        ->approveEmail($fixture['order']->fresh(), [], $fixture['user']);
})->throws(ValidationException::class);

it('allows no attachment with explicit confirmation', function () {
    $fixture = stage5SupplierOrderFixture();
    app(SupplierOrderEmailDraftService::class)
        ->prepareDraft($fixture['order'], ['auto_export' => false], $fixture['user']);

    $result = app(SupplierOrderEmailApprovalService::class)
        ->approveEmail($fixture['order']->fresh(), ['confirm_no_attachment' => true], $fixture['user']);

    expect($result['supplier_order']->no_attachment_confirmed)->toBeTrue();
});

it('cannot approve without recipients', function () {
    $fixture = stage5SupplierOrderFixture();
    app(SupplierOrderEmailDraftService::class)->prepareDraft($fixture['order'], [], $fixture['user']);
    EmailMessage::query()->firstOrFail()->forceFill(['to_json' => []])->save();

    app(SupplierOrderEmailApprovalService::class)
        ->approveEmail($fixture['order']->fresh(), [], $fixture['user']);
})->throws(ValidationException::class);

it('cannot approve without subject or body', function () {
    $fixture = stage5SupplierOrderFixture();
    app(SupplierOrderEmailDraftService::class)->prepareDraft($fixture['order'], [], $fixture['user']);
    EmailMessage::query()->firstOrFail()->forceFill(['subject' => ''])->save();

    app(SupplierOrderEmailApprovalService::class)
        ->approveEmail($fixture['order']->fresh(), [], $fixture['user']);
})->throws(ValidationException::class);
