<?php

use App\Enums\OrderProposalItemStatus;
use App\Enums\OrderProposalStatus;
use App\Enums\SupplierOrderStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\CalculationRun;
use App\Models\Company;
use App\Models\EmailMessage;
use App\Models\LogisticsRecord;
use App\Models\OrderProposal;
use App\Models\OrderProposalItem;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierContact;
use App\Models\SupplierOrder;
use App\Models\SupplierOrderItem;
use App\Models\User;
use App\Services\Supply\SupplierOrderCreationService;
use App\Services\Supply\SupplierOrderEmailDraftService;
use App\Services\Supply\SupplierOrderExportService;
use App\Services\Supply\SupplierOrderSendService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function makeSupplierOrderProposalFixture(array $proposalOverrides = [], array $itemOverrides = []): array
{
    $company = Company::factory()->create(['name' => 'Demo Supply Co']);
    $supplier = Supplier::factory()->for($company)->create([
        'name' => 'Acme Manufacturing',
        'type' => 'manufacturer',
        'default_currency' => 'EUR',
    ]);
    $product = Product::factory()->for($company)->create([
        'sku' => 'AX-150',
        'name' => 'Axle Bearing 150',
    ]);
    $user = User::factory()->create(['role' => UserRole::SupplyManager]);
    $calculationRun = CalculationRun::factory()->create([
        'company_id' => $company->getKey(),
        'supplier_id' => $supplier->getKey(),
        'started_by_user_id' => $user->getKey(),
    ]);

    $proposal = OrderProposal::factory()->create(array_merge([
        'company_id' => $company->getKey(),
        'supplier_id' => $supplier->getKey(),
        'calculation_run_id' => $calculationRun->getKey(),
        'created_by_user_id' => $user->getKey(),
        'status' => OrderProposalStatus::Approved,
        'total_lines' => 1,
    ], $proposalOverrides));

    $item = OrderProposalItem::factory()->create(array_merge([
        'order_proposal_id' => $proposal->getKey(),
        'product_id' => $product->getKey(),
        'status' => OrderProposalItemStatus::Approved,
        'approved_quantity' => 156,
        'recommended_quantity' => 156,
        'requires_human_review' => false,
    ], $itemOverrides));

    return compact('company', 'supplier', 'product', 'user', 'calculationRun', 'proposal', 'item');
}

function makeSupplierOrderEmailFixture(): array
{
    $company = Company::factory()->create(['name' => 'Demo Supply Co']);
    $supplier = Supplier::factory()->for($company)->create([
        'name' => 'Acme Manufacturing',
        'type' => 'manufacturer',
        'default_language' => 'en',
        'default_currency' => 'EUR',
    ]);
    $product = Product::factory()->for($company)->create([
        'sku' => 'AX-150',
        'name' => 'Axle Bearing 150',
    ]);
    $order = SupplierOrder::factory()->create([
        'company_id' => $company->getKey(),
        'supplier_id' => $supplier->getKey(),
        'order_proposal_id' => null,
        'order_number' => 'PO-TEST-1',
        'status' => SupplierOrderStatus::Draft,
    ]);

    SupplierOrderItem::factory()->create([
        'supplier_order_id' => $order->getKey(),
        'product_id' => $product->getKey(),
        'ordered_quantity' => 156,
        'currency' => 'EUR',
    ]);

    $contact = SupplierContact::factory()->for($supplier)->create([
        'name' => 'Orders Desk',
        'email' => 'orders@example.test',
        'receives_orders' => true,
        'is_active' => true,
    ]);
    $user = User::factory()->create(['role' => UserRole::SupplyManager]);

    return compact('company', 'supplier', 'product', 'order', 'contact', 'user');
}

it('creates a supplier order from an approved proposal', function () {
    $fixture = makeSupplierOrderProposalFixture();

    $supplierOrder = app(SupplierOrderCreationService::class)
        ->createFromApprovedProposal($fixture['proposal'], $fixture['user']);

    expect($supplierOrder)->toBeInstanceOf(SupplierOrder::class)
        ->and($supplierOrder->items)->toHaveCount(1)
        ->and((float) $supplierOrder->items->first()->ordered_quantity)->toBe(156.0)
        ->and($fixture['proposal']->fresh()->status)->toBe(OrderProposalStatus::ConvertedToSupplierOrder)
        ->and(LogisticsRecord::query()->whereBelongsTo($supplierOrder)->exists())->toBeTrue()
        ->and(AuditLog::query()->where('event_type', 'supplier_order_created')->exists())->toBeTrue();
});

it('does not create a supplier order from a draft proposal', function () {
    $fixture = makeSupplierOrderProposalFixture([
        'status' => OrderProposalStatus::Draft,
    ]);

    app(SupplierOrderCreationService::class)
        ->createFromApprovedProposal($fixture['proposal'], $fixture['user']);
})->throws(ValidationException::class);

it('exports a supplier order to CSV with SKU and quantity', function () {
    Storage::fake(config('filesystems.default'));
    $fixture = makeSupplierOrderEmailFixture();

    $exportFile = app(SupplierOrderExportService::class)
        ->export($fixture['order'], $fixture['user'], ['format' => 'csv']);
    $content = Storage::get($exportFile->stored_path);

    expect($content)->toContain('AX-150')
        ->and($content)->toContain('156.000')
        ->and($exportFile->export_type)->toBe('supplier_order_csv');
});

it('exports a supplier order to JSON', function () {
    Storage::fake(config('filesystems.default'));
    $fixture = makeSupplierOrderEmailFixture();

    $exportFile = app(SupplierOrderExportService::class)
        ->export($fixture['order'], $fixture['user'], ['format' => 'json']);
    $payload = json_decode(Storage::get($exportFile->stored_path), true, flags: JSON_THROW_ON_ERROR);

    expect($payload['supplier_order']['order_number'])->toBe('PO-TEST-1')
        ->and($payload['items'][0]['sku'])->toBe('AX-150')
        ->and((float) $payload['items'][0]['ordered_quantity'])->toBe(156.0);
});

it('creates an email draft with supplier contact', function () {
    $fixture = makeSupplierOrderEmailFixture();

    $emailMessage = app(SupplierOrderEmailDraftService::class)
        ->prepareDraft($fixture['order'], $fixture['user']);

    expect($emailMessage->status)->toBe('draft')
        ->and($emailMessage->direction->value)->toBe('outbound')
        ->and($emailMessage->to_json)->toBe(['orders@example.test'])
        ->and($emailMessage->subject)->toContain('PO-TEST-1')
        ->and($emailMessage->body_text)->toContain('Please find attached our purchase order PO-TEST-1.')
        ->and($fixture['order']->fresh()->status)->toBe(SupplierOrderStatus::EmailPrepared);
});

it('does not send an unapproved supplier order email', function () {
    $fixture = makeSupplierOrderEmailFixture();

    app(SupplierOrderEmailDraftService::class)
        ->prepareDraft($fixture['order'], $fixture['user']);

    app(SupplierOrderSendService::class)
        ->send($fixture['order'], $fixture['user'], ['no_attachment_confirmed' => true]);
})->throws(ValidationException::class);

it('requires explicit confirmation to approve an email without attachments', function () {
    $fixture = makeSupplierOrderEmailFixture();
    $draftService = app(SupplierOrderEmailDraftService::class);

    $draftService->prepareDraft($fixture['order'], $fixture['user'], ['auto_export' => false]);
    $draftService->approveDraft($fixture['order'], $fixture['user']);
})->throws(ValidationException::class);

it('sends an approved email, writes audit log, and updates status', function () {
    $fixture = makeSupplierOrderEmailFixture();
    $draftService = app(SupplierOrderEmailDraftService::class);

    $draftService->prepareDraft($fixture['order'], $fixture['user']);
    $draftService->approveDraft($fixture['order'], $fixture['user']);

    $sentEmail = app(SupplierOrderSendService::class)
        ->send($fixture['order'], $fixture['user'], ['no_attachment_confirmed' => true]);

    $order = $fixture['order']->fresh();

    expect($sentEmail)->toBeInstanceOf(EmailMessage::class)
        ->and($sentEmail->status)->toBe('sent')
        ->and($sentEmail->message_id)->toStartWith('log-')
        ->and($order->status)->toBe(SupplierOrderStatus::Sent)
        ->and($order->email_message_id)->toBe((string) $sentEmail->id)
        ->and(AuditLog::query()->where('event_type', 'supplier_email_sent')->exists())->toBeTrue()
        ->and(AuditLog::query()->where('event_type', 'supplier_order.email_sent')->exists())->toBeTrue();
});
