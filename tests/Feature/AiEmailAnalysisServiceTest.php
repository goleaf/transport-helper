<?php

use App\Enums\EmailDirection;
use App\Enums\SupplierOrderStatus;
use App\Models\AiEmailExtraction;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\EmailAccount;
use App\Models\EmailMessage;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierConfirmation;
use App\Models\SupplierOrder;
use App\Models\SupplierOrderItem;
use App\Services\AI\Email\AiEmailAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('analyze inbound email creates ai extraction', function () {
    $fixture = stage6AnalysisFixture();

    $result = app(AiEmailAnalysisService::class)->analyze($fixture['email'], ['analyzer' => 'rule_based']);

    expect($result['extraction'])->toBeInstanceOf(AiEmailExtraction::class)
        ->and($result['extraction']->provider)->toBe('rule_based')
        ->and($result['extraction']->output_json['email_type'])->toBe('supplier_confirmation')
        ->and(AuditLog::query()->where('event_type', 'ai_extraction_created')->exists())->toBeTrue();
});

it('analyze rejects outbound email', function () {
    $fixture = stage6AnalysisFixture(EmailDirection::Outbound);

    app(AiEmailAnalysisService::class)->analyze($fixture['email'], ['analyzer' => 'rule_based']);
})->throws(ValidationException::class);

it('analyze does not apply business changes', function () {
    $fixture = stage6AnalysisFixture();

    app(AiEmailAnalysisService::class)->analyze($fixture['email'], [
        'analyzer' => 'fake',
        'fake_output' => stage6AnalysisOutput([
            'confirmed_items' => [
                ['sku' => 'SKU-1001', 'confirmed_quantity' => 120],
            ],
        ]),
    ]);

    expect($fixture['item']->fresh()->confirmed_quantity)->toBeNull()
        ->and($fixture['order']->fresh()->status)->toBe(SupplierOrderStatus::Sent)
        ->and(SupplierConfirmation::query()->count())->toBe(0);
});

it('force false does not duplicate existing extraction', function () {
    $fixture = stage6AnalysisFixture();

    app(AiEmailAnalysisService::class)->analyze($fixture['email'], ['analyzer' => 'rule_based']);
    app(AiEmailAnalysisService::class)->analyze($fixture['email'], ['analyzer' => 'rule_based']);

    expect(AiEmailExtraction::query()->count())->toBe(1);
});

it('force true creates new extraction', function () {
    $fixture = stage6AnalysisFixture();

    app(AiEmailAnalysisService::class)->analyze($fixture['email'], ['analyzer' => 'rule_based']);
    app(AiEmailAnalysisService::class)->analyze($fixture['email'], ['analyzer' => 'rule_based', 'force' => true]);

    expect(AiEmailExtraction::query()->count())->toBe(2);
});

it('fake analyzer output is stored', function () {
    $fixture = stage6AnalysisFixture();
    $output = stage6AnalysisOutput(['supplier_reference' => 'CONF-FAKE']);

    $result = app(AiEmailAnalysisService::class)->analyze($fixture['email'], [
        'analyzer' => 'fake',
        'fake_output' => $output,
    ]);

    expect($result['extraction']->output_json['supplier_reference'])->toBe('CONF-FAKE')
        ->and($result['extraction']->output_json['_raw_output']['supplier_reference'])->toBe('CONF-FAKE');
});

function stage6AnalysisFixture(EmailDirection $direction = EmailDirection::Inbound): array
{
    $company = Company::factory()->create();
    $supplier = Supplier::factory()->for($company)->create();
    $product = Product::factory()->for($company)->create(['sku' => 'SKU-1001']);
    $order = SupplierOrder::factory()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'order_number' => 'PO-20260701-1',
        'status' => SupplierOrderStatus::Sent,
    ]);
    $item = SupplierOrderItem::factory()->create([
        'supplier_order_id' => $order->id,
        'product_id' => $product->id,
        'ordered_quantity' => 156,
        'confirmed_quantity' => null,
    ]);
    $account = EmailAccount::factory()->for($company)->create(['provider' => 'manual']);
    $email = EmailMessage::factory()->create([
        'company_id' => $company->id,
        'email_account_id' => $account->id,
        'direction' => $direction,
        'from_email' => 'orders@acme.test',
        'subject' => 'Confirmation PO-20260701-1',
        'body_text' => 'We confirm SKU-1001 quantity 156 ready on 2026-07-15.',
        'related_supplier_id' => $supplier->id,
        'related_supplier_order_id' => $order->id,
        'status' => 'stored',
    ]);

    return compact('company', 'supplier', 'product', 'order', 'item', 'account', 'email');
}

function stage6AnalysisOutput(array $overrides = []): array
{
    return array_replace_recursive([
        'email_type' => 'supplier_confirmation',
        'supplier_order_number' => 'PO-20260701-1',
        'supplier_reference' => 'CONF-123',
        'confirmed_items' => [
            ['sku' => 'SKU-1001', 'confirmed_quantity' => 156],
        ],
        'dates' => ['ready_date' => '2026-07-15'],
        'carrier_quote' => [],
        'discrepancies' => [],
        'questions_to_supplier' => [],
        'confidence' => 0.91,
        'requires_human_review' => true,
        'human_review_reason' => 'test_review',
    ], $overrides);
}
