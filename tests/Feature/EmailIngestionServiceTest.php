<?php

use App\Enums\EmailDirection;
use App\Enums\SupplierOrderStatus;
use App\Exceptions\NotConfiguredYetException;
use App\Models\AiEmailExtraction;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\EmailAccount;
use App\Models\EmailAttachment;
use App\Models\EmailMessage;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierContact;
use App\Models\SupplierOrder;
use App\Models\SupplierOrderItem;
use App\Services\Email\EmailIngestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('manual inbound email is stored', function () {
    $fixture = stage6IngestionFixture();

    $result = app(EmailIngestionService::class)->ingest($fixture['company'], $fixture['account'], 'manual', [
        'messages' => [stage6ManualMessage()],
    ]);

    $email = $result['messages'][0];

    expect($result['summary']['stored_count'])->toBe(1)
        ->and($email->direction)->toBe(EmailDirection::Inbound)
        ->and($email->from_email)->toBe('orders@acme.test')
        ->and($email->related_supplier_id)->toBe($fixture['supplier']->id)
        ->and(AuditLog::query()->where('event_type', 'email_received')->exists())->toBeTrue();
});

it('duplicate message id is skipped', function () {
    $fixture = stage6IngestionFixture();
    $message = stage6ManualMessage(['message_id' => 'duplicate-message']);

    app(EmailIngestionService::class)->ingest($fixture['company'], $fixture['account'], 'manual', ['messages' => [$message]]);
    $result = app(EmailIngestionService::class)->ingest($fixture['company'], $fixture['account'], 'manual', ['messages' => [$message]]);

    expect(EmailMessage::query()->where('message_id', 'duplicate-message')->count())->toBe(1)
        ->and($result['summary']['duplicate_count'])->toBe(1);
});

it('email links to supplier order by order number', function () {
    $fixture = stage6IngestionFixture();

    $result = app(EmailIngestionService::class)->ingest($fixture['company'], $fixture['account'], 'manual', [
        'messages' => [stage6ManualMessage(['subject' => 'Confirmation '.$fixture['order']->order_number])],
    ]);

    expect($result['messages'][0]->related_supplier_order_id)->toBe($fixture['order']->id);
});

it('unknown supplier marks needs review', function () {
    $company = Company::factory()->create();
    $account = EmailAccount::factory()->for($company)->create(['provider' => 'manual']);

    $result = app(EmailIngestionService::class)->ingest($company, $account, 'manual', [
        'messages' => [stage6ManualMessage(['from_email' => 'unknown@example.test'])],
    ]);

    expect($result['messages'][0]->related_supplier_id)->toBeNull()
        ->and($result['messages'][0]->status)->toBe('needs_review');
});

it('attachments are stored', function () {
    Storage::fake('local');
    $fixture = stage6IngestionFixture();

    app(EmailIngestionService::class)->ingest($fixture['company'], $fixture['account'], 'manual', [
        'messages' => [
            stage6ManualMessage([
                'attachments' => [
                    [
                        'original_filename' => '../confirmation.txt',
                        'content' => 'attached confirmation',
                        'mime_type' => 'text/plain',
                    ],
                ],
            ]),
        ],
    ]);

    $attachment = EmailAttachment::query()->firstOrFail();

    Storage::assertExists($attachment->stored_path);
    expect($attachment->original_filename)->toBe('confirmation.txt');
});

it('ingest with analyze sync creates extraction', function () {
    $fixture = stage6IngestionFixture();

    app(EmailIngestionService::class)->ingest($fixture['company'], $fixture['account'], 'manual', [
        'messages' => [stage6ManualMessage()],
        'analyze' => true,
        'sync_analysis' => true,
        'analyzer' => 'rule_based',
    ]);

    expect(AiEmailExtraction::query()->count())->toBe(1);
});

it('placeholder gmail provider throws not configured', function () {
    $company = Company::factory()->create();

    app(EmailIngestionService::class)->ingest($company, null, 'gmail', []);
})->throws(NotConfiguredYetException::class);

function stage6IngestionFixture(): array
{
    $company = Company::factory()->create();
    $supplier = Supplier::factory()->for($company)->create(['name' => 'Acme Manufacturing']);
    SupplierContact::factory()->for($supplier)->create(['email' => 'orders@acme.test']);
    $product = Product::factory()->for($company)->create(['sku' => 'SKU-1001']);
    $order = SupplierOrder::factory()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'order_number' => 'PO-20260701-1',
        'status' => SupplierOrderStatus::Sent,
    ]);
    SupplierOrderItem::factory()->create([
        'supplier_order_id' => $order->id,
        'product_id' => $product->id,
        'ordered_quantity' => 156,
    ]);
    $account = EmailAccount::factory()->for($company)->create(['provider' => 'manual']);

    return compact('company', 'supplier', 'product', 'order', 'account');
}

function stage6ManualMessage(array $overrides = []): array
{
    return array_replace([
        'message_id' => 'manual-stage6-1',
        'thread_id' => 'thread-stage6-1',
        'from_email' => 'orders@acme.test',
        'to' => ['supply@company.test'],
        'subject' => 'Confirmation PO-20260701-1',
        'body_text' => 'We confirm SKU-1001 quantity 156 ready on 2026-07-15.',
        'received_at' => '2026-07-02 10:00:00',
        'attachments' => [],
    ], $overrides);
}
