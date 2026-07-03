<?php

use App\Enums\EmailDirection;
use App\Enums\SupplierOrderStatus;
use App\Models\AiEmailExtraction;
use App\Models\AuditLog;
use App\Models\EmailAttachment;
use App\Models\EmailMessage;
use App\Models\ExportFile;
use App\Services\Supply\SupplierOrders\SupplierOrderEmailDraftService;
use App\Services\Supply\SupplierOrders\SupplierOrderExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

require_once __DIR__.'/SupplierOrderStage5Support.php';

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake(config('filesystems.default'));
});

it('prepares outbound email draft with supplier contact', function () {
    $fixture = stage5SupplierOrderFixture();

    $result = app(SupplierOrderEmailDraftService::class)
        ->prepareDraft($fixture['order'], [], $fixture['user']);

    /** @var EmailMessage $email */
    $email = $result['email_message'];

    expect(EmailMessage::query()->count())->toBe(1)
        ->and($email->direction)->toBe(EmailDirection::Outbound)
        ->and($email->status)->toBe('draft')
        ->and($email->subject)->toContain('PO-TEST-1')
        ->and($email->to_json)->toBe(['orders@example.test'])
        ->and($fixture['order']->fresh()->status)->toBe(SupplierOrderStatus::EmailPrepared)
        ->and($fixture['order']->fresh()->email_message_id)->toBe((string) $email->id)
        ->and(AuditLog::query()->where('event_type', 'supplier_email_draft_prepared')->exists())->toBeTrue();
});

it('auto exports and attaches when no export exists', function () {
    $fixture = stage5SupplierOrderFixture();

    app(SupplierOrderEmailDraftService::class)->prepareDraft($fixture['order'], [], $fixture['user']);

    expect(ExportFile::query()->count())->toBe(1)
        ->and(EmailAttachment::query()->count())->toBe(1);
});

it('uses existing export file when selected', function () {
    $fixture = stage5SupplierOrderFixture();
    $export = app(SupplierOrderExportService::class)
        ->export($fixture['order'], 'csv', [], $fixture['user'])['export_file'];

    $result = app(SupplierOrderEmailDraftService::class)
        ->prepareDraft($fixture['order'], ['export_file_id' => $export->id], $fixture['user']);

    expect(ExportFile::query()->count())->toBe(1)
        ->and($result['email_message']->attachments->first()->stored_path)->toBe($export->stored_path);
});

it('fails without active order contact', function () {
    $fixture = stage5SupplierOrderFixture();
    $fixture['contact']->forceFill(['receives_orders' => false])->save();

    app(SupplierOrderEmailDraftService::class)->prepareDraft($fixture['order'], [], $fixture['user']);
})->throws(ValidationException::class);

it('uses Lithuanian template when supplier language is lt', function () {
    $fixture = stage5SupplierOrderFixture(supplierOverrides: ['default_language' => 'lt']);

    $result = app(SupplierOrderEmailDraftService::class)
        ->prepareDraft($fixture['order'], [], $fixture['user']);

    expect($result['email_message']->body_text)->toContain('Prisegame');
});

it('does not create email extraction records', function () {
    $fixture = stage5SupplierOrderFixture();

    app(SupplierOrderEmailDraftService::class)->prepareDraft($fixture['order'], [], $fixture['user']);

    expect(AiEmailExtraction::query()->count())->toBe(0);
});
