<?php

use App\Contracts\AI\AiEmailAnalyzerInterface;
use App\Enums\EmailDirection;
use App\Enums\SupplierOrderStatus;
use App\Enums\UserRole;
use App\Jobs\AnalyzeInboundEmailJob;
use App\Models\AiEmailExtraction;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\EmailAccount;
use App\Models\EmailMessage;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierConfirmation;
use App\Models\SupplierContact;
use App\Models\SupplierOrder;
use App\Models\SupplierOrderItem;
use App\Models\User;
use App\Services\AI\AiEmailExtractionReviewService;
use App\Services\AI\AiEmailExtractionValidationService;
use App\Services\Email\EmailIngestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

function makeEmailAiFixture(): array
{
    $company = Company::factory()->create(['name' => 'Demo Supply Co']);
    $supplier = Supplier::factory()->for($company)->create([
        'name' => 'Acme Manufacturing',
        'type' => 'manufacturer',
    ]);
    $contact = SupplierContact::factory()->for($supplier)->create([
        'email' => 'orders@acme.test',
        'receives_orders' => true,
        'is_active' => true,
    ]);
    $product = Product::factory()->for($company)->create([
        'sku' => 'AX-150',
        'name' => 'Axle Bearing 150',
    ]);
    $supplierOrder = SupplierOrder::factory()->create([
        'company_id' => $company->getKey(),
        'supplier_id' => $supplier->getKey(),
        'order_proposal_id' => null,
        'order_number' => 'PO-EMAIL-1',
        'status' => SupplierOrderStatus::Sent,
    ]);
    $supplierOrderItem = SupplierOrderItem::factory()->create([
        'supplier_order_id' => $supplierOrder->getKey(),
        'product_id' => $product->getKey(),
        'ordered_quantity' => 156,
    ]);
    $emailAccount = EmailAccount::factory()->for($company)->create([
        'provider' => 'manual',
        'email_address' => 'supply@example.test',
    ]);
    $user = User::factory()->create(['role' => UserRole::SupplyManager]);

    return compact('company', 'supplier', 'contact', 'product', 'supplierOrder', 'supplierOrderItem', 'emailAccount', 'user');
}

function aiEmailOutput(array $overrides = []): array
{
    return array_merge([
        'email_type' => 'supplier_confirmation',
        'supplier_order_number' => 'PO-EMAIL-1',
        'supplier_reference' => 'CONF-001',
        'confirmed_items' => [
            [
                'sku' => 'AX-150',
                'confirmed_quantity' => 156,
            ],
        ],
        'dates' => [
            'confirmation_date' => '2026-07-03',
            'ready_date' => '2026-07-10',
            'shipping_date' => '2026-07-11',
            'expected_arrival_date' => '2026-07-20',
        ],
        'carrier_quote' => [],
        'discrepancies' => [],
        'questions_to_supplier' => [],
        'confidence' => 0.95,
        'requires_human_review' => false,
        'human_review_reason' => null,
    ], $overrides);
}

function makeAiEmailMessage(array $fixture): EmailMessage
{
    return EmailMessage::factory()->create([
        'company_id' => $fixture['company']->getKey(),
        'email_account_id' => $fixture['emailAccount']->getKey(),
        'direction' => EmailDirection::Inbound,
        'message_id' => 'inbound-confirmation-1',
        'thread_id' => 'thread-1',
        'from_email' => 'orders@acme.test',
        'subject' => 'Confirmation for PO-EMAIL-1',
        'body_text' => 'Confirmed AX-150 quantity 156 ready 2026-07-10.',
        'related_supplier_id' => $fixture['supplier']->getKey(),
        'related_supplier_order_id' => $fixture['supplierOrder']->getKey(),
        'status' => 'received',
    ]);
}

function makeAiExtraction(array $fixture, array $outputOverrides = []): AiEmailExtraction
{
    $email = makeAiEmailMessage($fixture);

    return AiEmailExtraction::factory()->create([
        'email_message_id' => $email->getKey(),
        'output_json' => aiEmailOutput($outputOverrides),
        'confidence' => $outputOverrides['confidence'] ?? 0.95,
        'requires_human_review' => true,
        'review_reason' => 'pending_human_approval',
    ]);
}

it('stores a manual inbound email', function () {
    $fixture = makeEmailAiFixture();

    $result = app(EmailIngestionService::class)->ingest($fixture['emailAccount'], [
        'messages' => [
            [
                'message_id' => 'manual-1',
                'thread_id' => 'thread-po',
                'from_email' => 'orders@acme.test',
                'to' => ['supply@example.test'],
                'subject' => 'Re: PO-EMAIL-1 confirmation',
                'body_text' => 'Confirmed.',
                'received_at' => '2026-07-03 10:00:00',
                'attachments' => [
                    [
                        'filename' => 'confirmation.txt',
                        'content' => 'attached confirmation',
                        'mime_type' => 'text/plain',
                    ],
                ],
            ],
        ],
    ]);

    $email = $result['stored'][0];

    expect($result['stored_count'])->toBe(1)
        ->and($email->message_id)->toBe('manual-1')
        ->and($email->related_supplier_id)->toBe($fixture['supplier']->getKey())
        ->and($email->related_supplier_order_id)->toBe($fixture['supplierOrder']->getKey())
        ->and($email->attachments)->toHaveCount(1);
});

it('ignores duplicate message ids', function () {
    $fixture = makeEmailAiFixture();
    $message = [
        'message_id' => 'duplicate-1',
        'from_email' => 'orders@acme.test',
        'subject' => 'PO-EMAIL-1',
        'body_text' => 'First copy.',
    ];

    app(EmailIngestionService::class)->ingest($fixture['emailAccount'], ['messages' => [$message]]);
    $result = app(EmailIngestionService::class)->ingest($fixture['emailAccount'], ['messages' => [$message]]);

    expect($result['stored_count'])->toBe(0)
        ->and($result['duplicate_count'])->toBe(1)
        ->and(EmailMessage::query()->where('message_id', 'duplicate-1')->count())->toBe(1);
});

it('requires review for low confidence AI extraction', function () {
    $fixture = makeEmailAiFixture();
    $extraction = makeAiExtraction($fixture, [
        'confidence' => 0.4,
        'requires_human_review' => true,
        'human_review_reason' => 'low model confidence',
    ]);

    $validation = app(AiEmailExtractionValidationService::class)->validate($extraction);

    expect($validation['status'])->toBe('needs_review')
        ->and($validation['reasons'])->toContain('low_confidence');
});

it('requires review when AI extraction contains an unknown SKU', function () {
    $fixture = makeEmailAiFixture();
    $extraction = makeAiExtraction($fixture, [
        'confirmed_items' => [
            [
                'sku' => 'UNKNOWN-SKU',
                'confirmed_quantity' => 1,
            ],
        ],
    ]);

    $validation = app(AiEmailExtractionValidationService::class)->validate($extraction);

    expect($validation['status'])->toBe('needs_review')
        ->and($validation['reasons'])->toContain('unknown_sku');
});

it('creates a supplier confirmation when accepted', function () {
    $fixture = makeEmailAiFixture();
    $extraction = makeAiExtraction($fixture);

    $result = app(AiEmailExtractionReviewService::class)->accept($extraction, $fixture['user']);
    $confirmation = SupplierConfirmation::query()->with('items')->firstOrFail();

    expect($result['applied']->is($confirmation))->toBeTrue()
        ->and($confirmation->supplier_order_id)->toBe($fixture['supplierOrder']->getKey())
        ->and($confirmation->supplier_reference)->toBe('CONF-001')
        ->and($confirmation->items)->toHaveCount(1)
        ->and((float) $confirmation->items->first()->confirmed_quantity)->toBe(156.0)
        ->and($extraction->fresh()->accepted_at)->not->toBeNull()
        ->and(AuditLog::query()->where('event_type', 'ai_email_extraction.accepted')->exists())->toBeTrue();
});

it('does not change business records when rejected', function () {
    $fixture = makeEmailAiFixture();
    $extraction = makeAiExtraction($fixture);

    app(AiEmailExtractionReviewService::class)->reject($extraction, $fixture['user']);

    expect(SupplierConfirmation::query()->count())->toBe(0)
        ->and($fixture['supplierOrder']->fresh()->status)->toBe(SupplierOrderStatus::Sent)
        ->and($extraction->fresh()->rejected_at)->not->toBeNull()
        ->and(AuditLog::query()->where('event_type', 'ai_email_extraction.rejected')->exists())->toBeTrue();
});

it('uses a mocked AI analyzer for inbound email analysis', function () {
    $fixture = makeEmailAiFixture();
    $email = makeAiEmailMessage($fixture);

    $this->mock(AiEmailAnalyzerInterface::class, function (MockInterface $mock): void {
        $mock->shouldReceive('analyze')
            ->once()
            ->andReturn(aiEmailOutput([
                'confidence' => 0.81,
                'requires_human_review' => false,
            ]));
    });

    app(AnalyzeInboundEmailJob::class, ['emailMessageId' => $email->id])
        ->handle(app(AiEmailAnalyzerInterface::class), app(AiEmailExtractionValidationService::class));

    $extraction = AiEmailExtraction::query()->firstOrFail();

    expect($extraction->email_message_id)->toBe($email->id)
        ->and($extraction->output_json['email_type'])->toBe('supplier_confirmation')
        ->and((float) $extraction->confidence)->toBe(0.81)
        ->and($extraction->requires_human_review)->toBeTrue();
});

it('keeps calculation engine independent from AI contracts', function () {
    $source = file_get_contents(app_path('Services/Supply/OrderNeedCalculator.php'));

    expect($source)->not->toContain('App\\Contracts\\AI')
        ->and($source)->not->toContain('AiEmailAnalyzerInterface')
        ->and($source)->not->toContain('AiEmailReplyDraftGeneratorInterface')
        ->and($source)->not->toContain('AiEmailFormExtractorInterface');
});
