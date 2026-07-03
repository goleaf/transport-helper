<?php

use App\Enums\EmailDirection;
use App\Enums\SupplierOrderStatus;
use App\Enums\UserRole;
use App\Models\AiEmailExtraction;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\EmailMessage;
use App\Models\Supplier;
use App\Models\SupplierConfirmation;
use App\Models\SupplierOrder;
use App\Models\User;
use App\Services\AI\Email\AiEmailExtractionReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('accept extraction sets review fields', function () {
    $fixture = stage6ReviewFixture();

    app(AiEmailExtractionReviewService::class)->accept($fixture['extraction'], $fixture['user'], ['note' => 'Looks correct']);

    $extraction = $fixture['extraction']->fresh();

    expect($extraction->accepted_at)->not->toBeNull()
        ->and($extraction->reviewed_by_user_id)->toBe($fixture['user']->id)
        ->and($extraction->requires_human_review)->toBeFalse()
        ->and(AuditLog::query()->where('event_type', 'ai_extraction_accepted')->exists())->toBeTrue();
});

it('reject extraction sets rejected at', function () {
    $fixture = stage6ReviewFixture();

    app(AiEmailExtractionReviewService::class)->reject($fixture['extraction'], $fixture['user'], ['note' => 'Wrong order']);

    $extraction = $fixture['extraction']->fresh();

    expect($extraction->rejected_at)->not->toBeNull()
        ->and($extraction->accepted_at)->toBeNull()
        ->and(AuditLog::query()->where('event_type', 'ai_extraction_rejected')->exists())->toBeTrue();
});

it('mark needs review keeps requires review true', function () {
    $fixture = stage6ReviewFixture();

    app(AiEmailExtractionReviewService::class)->markNeedsReview($fixture['extraction'], $fixture['user'], ['note' => 'Ambiguous date']);

    $extraction = $fixture['extraction']->fresh();

    expect($extraction->requires_human_review)->toBeTrue()
        ->and($extraction->review_reason)->toBe('Ambiguous date')
        ->and(AuditLog::query()->where('event_type', 'ai_extraction_marked_needs_review')->exists())->toBeTrue();
});

it('accepting extraction does not create supplier confirmation', function () {
    $fixture = stage6ReviewFixture();

    app(AiEmailExtractionReviewService::class)->accept($fixture['extraction'], $fixture['user']);

    expect(SupplierConfirmation::query()->count())->toBe(0)
        ->and($fixture['order']->fresh()->status)->toBe(SupplierOrderStatus::Sent);
});

it('cannot accept rejected extraction', function () {
    $fixture = stage6ReviewFixture();
    app(AiEmailExtractionReviewService::class)->reject($fixture['extraction'], $fixture['user'], ['note' => 'Reject first']);

    app(AiEmailExtractionReviewService::class)->accept($fixture['extraction'], $fixture['user']);
})->throws(ValidationException::class);

function stage6ReviewFixture(): array
{
    $company = Company::factory()->create();
    $supplier = Supplier::factory()->for($company)->create();
    $order = SupplierOrder::factory()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'status' => SupplierOrderStatus::Sent,
        'order_number' => 'PO-REVIEW-1',
    ]);
    $email = EmailMessage::factory()->create([
        'company_id' => $company->id,
        'direction' => EmailDirection::Inbound,
        'related_supplier_id' => $supplier->id,
        'related_supplier_order_id' => $order->id,
        'status' => 'needs_review',
    ]);
    $extraction = AiEmailExtraction::factory()->create([
        'email_message_id' => $email->id,
        'output_json' => [
            'email_type' => 'supplier_confirmation',
            'supplier_order_number' => 'PO-REVIEW-1',
            'confirmed_items' => [],
            'dates' => [],
            'carrier_quote' => [],
            'discrepancies' => [],
            'questions_to_supplier' => [],
            'confidence' => 0.7,
            'requires_human_review' => true,
        ],
        'requires_human_review' => true,
    ]);
    $user = User::factory()->create(['role' => UserRole::SupplyManager]);

    return compact('company', 'supplier', 'order', 'email', 'extraction', 'user');
}
