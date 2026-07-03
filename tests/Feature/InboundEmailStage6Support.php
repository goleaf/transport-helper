<?php

use App\Enums\EmailDirection;
use App\Enums\SupplierOrderStatus;
use App\Enums\UserRole;
use App\Models\AiEmailExtraction;
use App\Models\Company;
use App\Models\EmailAccount;
use App\Models\EmailMessage;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierContact;
use App\Models\SupplierOrder;
use App\Models\SupplierOrderItem;
use App\Models\User;

function inboundEmailStage6Fixture(): array
{
    $company = Company::factory()->create(['name' => 'Stage 6 Company']);
    $supplier = Supplier::factory()->for($company)->create(['name' => 'Acme Supplier']);
    $contact = SupplierContact::factory()->for($supplier)->create([
        'email' => 'orders@acme.test',
        'receives_orders' => true,
        'is_active' => true,
    ]);
    $product = Product::factory()->for($company)->create([
        'sku' => 'SKU-1001',
        'manufacturer_sku' => 'MFG-1001',
    ]);
    $supplierOrder = SupplierOrder::factory()->create([
        'company_id' => $company->getKey(),
        'supplier_id' => $supplier->getKey(),
        'order_proposal_id' => null,
        'order_number' => 'PO-20260703-1',
        'status' => SupplierOrderStatus::Sent,
    ]);
    $supplierOrderItem = SupplierOrderItem::factory()->create([
        'supplier_order_id' => $supplierOrder->getKey(),
        'product_id' => $product->getKey(),
        'ordered_quantity' => 156,
        'confirmed_quantity' => null,
    ]);
    $emailAccount = EmailAccount::factory()->for($company)->create([
        'provider' => 'manual',
        'email_address' => 'supply@company.test',
    ]);
    $user = User::factory()->create(['role' => UserRole::SupplyManager]);

    return compact('company', 'supplier', 'contact', 'product', 'supplierOrder', 'supplierOrderItem', 'emailAccount', 'user');
}

function inboundEmailStage6Message(array $fixture, array $overrides = []): EmailMessage
{
    return EmailMessage::factory()->create(array_merge([
        'company_id' => $fixture['company']->getKey(),
        'email_account_id' => $fixture['emailAccount']->getKey(),
        'direction' => EmailDirection::Inbound,
        'message_id' => 'stage6-message-1',
        'thread_id' => 'thread-stage6',
        'from_email' => 'orders@acme.test',
        'subject' => 'Re: Purchase order PO-20260703-1',
        'body_text' => 'Confirmed SKU-1001 quantity 156 ready 2026-07-10.',
        'related_supplier_id' => $fixture['supplier']->getKey(),
        'related_supplier_order_id' => $fixture['supplierOrder']->getKey(),
        'status' => 'linked',
    ], $overrides));
}

function inboundEmailStage6Output(array $overrides = []): array
{
    return array_merge([
        'email_type' => 'supplier_confirmation',
        'supplier_order_number' => 'PO-20260703-1',
        'supplier_reference' => 'CONF-6',
        'confirmed_items' => [
            [
                'sku' => 'SKU-1001',
                'confirmed_quantity' => 156,
            ],
        ],
        'dates' => [
            'ready_date' => '2026-07-10',
            'shipping_date' => '2026-07-11',
        ],
        'carrier_quote' => [],
        'discrepancies' => [],
        'questions_to_supplier' => [],
        'confidence' => 0.95,
        'requires_human_review' => false,
        'human_review_reason' => null,
    ], $overrides);
}

function inboundEmailStage6Extraction(array $fixture, array $outputOverrides = []): AiEmailExtraction
{
    return AiEmailExtraction::factory()->create([
        'email_message_id' => inboundEmailStage6Message($fixture)->getKey(),
        'provider' => 'fake',
        'model' => 'fake',
        'output_json' => inboundEmailStage6Output($outputOverrides),
        'confidence' => $outputOverrides['confidence'] ?? 0.95,
        'requires_human_review' => true,
        'review_reason' => 'pending_human_approval',
        'reviewed_by_user_id' => null,
    ]);
}
