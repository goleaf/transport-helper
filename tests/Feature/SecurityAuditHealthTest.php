<?php

use App\Enums\EmailDirection;
use App\Enums\FormAutofillRunStatus;
use App\Enums\FormTemplateContextType;
use App\Enums\FormTemplateFormatType;
use App\Enums\OrderProposalItemStatus;
use App\Enums\OrderProposalStatus;
use App\Enums\SupplierOrderStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\CalculationRun;
use App\Models\Company;
use App\Models\EmailAccount;
use App\Models\EmailMessage;
use App\Models\FormAutofillFieldValue;
use App\Models\FormAutofillRun;
use App\Models\FormTemplate;
use App\Models\IntegrationConnection;
use App\Models\OrderProposal;
use App\Models\OrderProposalItem;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierOrder;
use App\Models\SupplierOrderItem;
use App\Models\User;
use App\Services\FormAutofill\FormAutofillApplyService;
use App\Services\Supply\OrderProposalDecisionService;
use App\Services\Supply\SupplierOrderSendService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function makeSecurityProposalFixture(): array
{
    $company = Company::factory()->create(['default_currency' => 'EUR']);
    $supplier = Supplier::factory()->for($company)->create(['type' => 'manufacturer']);
    $product = Product::factory()->for($company)->create(['sku' => 'SEC-001']);
    $user = User::factory()->create(['role' => UserRole::SupplyManager]);
    $calculationRun = CalculationRun::factory()->create([
        'company_id' => $company->getKey(),
        'supplier_id' => $supplier->getKey(),
        'started_by_user_id' => $user->getKey(),
    ]);
    $proposal = OrderProposal::factory()->create([
        'company_id' => $company->getKey(),
        'supplier_id' => $supplier->getKey(),
        'calculation_run_id' => $calculationRun->getKey(),
        'status' => OrderProposalStatus::Draft,
        'created_by_user_id' => $user->getKey(),
    ]);
    $item = OrderProposalItem::factory()->create([
        'order_proposal_id' => $proposal->getKey(),
        'product_id' => $product->getKey(),
        'status' => OrderProposalItemStatus::NeedsReview,
        'recommended_quantity' => 144,
        'approved_quantity' => null,
        'requires_human_review' => true,
    ]);

    return compact('company', 'supplier', 'product', 'user', 'calculationRun', 'proposal', 'item');
}

function makeSecuritySupplierOrderEmailFixture(): array
{
    $company = Company::factory()->create(['default_currency' => 'EUR']);
    $supplier = Supplier::factory()->for($company)->create(['type' => 'manufacturer']);
    $product = Product::factory()->for($company)->create(['sku' => 'SEC-MAIL-001']);
    $user = User::factory()->create(['role' => UserRole::SupplyManager]);
    $order = SupplierOrder::factory()->create([
        'company_id' => $company->getKey(),
        'supplier_id' => $supplier->getKey(),
        'order_proposal_id' => null,
        'order_number' => 'PO-SEC-MAIL',
        'status' => SupplierOrderStatus::Approved,
        'email_approved_at' => now(),
        'email_approved_by_user_id' => $user->getKey(),
        'no_attachment_confirmed' => true,
    ]);

    SupplierOrderItem::factory()->create([
        'supplier_order_id' => $order->getKey(),
        'product_id' => $product->getKey(),
        'ordered_quantity' => 156,
    ]);

    $approvedEmail = EmailMessage::factory()->create([
        'company_id' => $company->getKey(),
        'email_account_id' => null,
        'direction' => EmailDirection::Outbound,
        'message_id' => null,
        'from_email' => 'procurement@example.test',
        'to_json' => ['orders@supplier.test'],
        'subject' => 'Order PO-SEC-MAIL',
        'body_text' => 'Please confirm PO-SEC-MAIL.',
        'received_at' => null,
        'sent_at' => null,
        'related_supplier_id' => $supplier->getKey(),
        'related_supplier_order_id' => $order->getKey(),
        'status' => 'approved',
    ]);

    $order->forceFill([
        'email_message_id' => (string) $approvedEmail->getKey(),
    ])->save();

    return compact('company', 'supplier', 'product', 'user', 'order', 'approvedEmail');
}

function makeSecurityCustomFormAutofillFixture(): array
{
    $company = Company::factory()->create(['default_currency' => 'EUR']);
    $user = User::factory()->create(['role' => UserRole::SupplyManager]);
    $email = EmailMessage::factory()->create([
        'company_id' => $company->getKey(),
        'email_account_id' => null,
        'direction' => EmailDirection::Inbound,
        'related_supplier_order_id' => null,
        'status' => 'received',
    ]);
    $template = FormTemplate::factory()->create([
        'company_id' => $company->getKey(),
        'supplier_id' => null,
        'carrier_id' => null,
        'context_type' => FormTemplateContextType::CustomEmailForm,
        'format_type' => FormTemplateFormatType::InternalHtml,
    ]);
    $run = FormAutofillRun::factory()->create([
        'company_id' => $company->getKey(),
        'email_message_id' => $email->getKey(),
        'form_template_id' => $template->getKey(),
        'ai_email_extraction_id' => null,
        'status' => FormAutofillRunStatus::Validated,
        'created_by_user_id' => $user->getKey(),
    ]);

    FormAutofillFieldValue::factory()->create([
        'form_autofill_run_id' => $run->getKey(),
        'field_key' => 'notes',
        'final_value' => 'Supplier asked to use portal form.',
        'requires_review' => false,
    ]);

    return compact('company', 'user', 'email', 'template', 'run');
}

it('prevents a viewer from approving supplier orders', function () {
    $viewer = User::factory()->create(['role' => UserRole::Viewer]);
    $order = SupplierOrder::factory()->create([
        'order_proposal_id' => null,
        'status' => SupplierOrderStatus::AwaitingApproval,
    ]);

    expect($viewer->cannot('approve', $order))->toBeTrue();
});

it('allows a supply manager to approve proposals but not manage integrations', function () {
    $fixture = makeSecurityProposalFixture();
    $supplyManager = User::factory()->create(['role' => UserRole::SupplyManager]);

    expect($supplyManager->can('approve', $fixture['proposal']))->toBeTrue()
        ->and($supplyManager->cannot('manage', IntegrationConnection::class))->toBeTrue();
});

it('allows an admin to manage integrations', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    expect($admin->can('manage', IntegrationConnection::class))->toBeTrue();
});

it('writes an audit log when quantity is adjusted', function () {
    $fixture = makeSecurityProposalFixture();

    app(OrderProposalDecisionService::class)->adjustItem($fixture['item'], $fixture['user'], [
        'quantity' => 156,
        'reason' => 'Round up to full carton.',
    ]);

    expect(AuditLog::query()
        ->where('event_type', 'order_quantity_adjusted')
        ->where('auditable_id', $fixture['item']->getKey())
        ->where('user_id', $fixture['user']->getKey())
        ->exists())->toBeTrue();
});

it('writes an audit log when email is sent', function () {
    $fixture = makeSecuritySupplierOrderEmailFixture();

    app(SupplierOrderSendService::class)->send($fixture['order'], $fixture['user'], [
        'no_attachment_confirmed' => true,
    ]);

    expect(AuditLog::query()
        ->where('event_type', 'supplier_order.email_sent')
        ->where('auditable_id', $fixture['order']->getKey())
        ->where('user_id', $fixture['user']->getKey())
        ->exists())->toBeTrue();
});

it('writes an audit log when form autofill application gate is checked', function () {
    $fixture = makeSecurityCustomFormAutofillFixture();

    app(FormAutofillApplyService::class)->apply($fixture['run'], $fixture['user']);

    expect(AuditLog::query()
        ->where('event_type', 'form_autofill_apply_gate_checked')
        ->where('auditable_id', $fixture['run']->getKey())
        ->where('user_id', $fixture['user']->getKey())
        ->exists())->toBeTrue();
});

it('encrypts credential configs at rest', function () {
    $company = Company::factory()->create();
    $emailAccount = EmailAccount::factory()->for($company)->create([
        'encrypted_config' => [
            'smtp' => [
                'username' => 'smtp-user',
                'password' => 'smtp-secret',
            ],
        ],
    ]);
    $integration = IntegrationConnection::factory()->for($company)->create([
        'type' => 'google_sheets',
        'encrypted_config' => [
            'api_key' => 'api-secret',
            'service_account_json' => '{"client_email":"bot@example.test"}',
        ],
    ]);

    expect($emailAccount->encrypted_config['smtp']['password'])->toBe('smtp-secret')
        ->and($emailAccount->getRawOriginal('encrypted_config'))->not->toContain('smtp-secret')
        ->and($integration->encrypted_config['api_key'])->toBe('api-secret')
        ->and($integration->getRawOriginal('encrypted_config'))->not->toContain('api-secret');
});

it('returns success with warnings from the health check command', function () {
    Storage::fake(config('filesystems.default'));

    $exitCode = Artisan::call('supply:health-check');
    $output = Artisan::output();

    expect($exitCode)->toBe(0)
        ->and($output)->toContain('[OK] Database connection')
        ->and($output)->toContain('[WARN]');
});
