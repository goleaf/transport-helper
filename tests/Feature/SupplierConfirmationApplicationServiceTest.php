<?php

use App\Enums\EmailDirection;
use App\Enums\FormAutofillRunStatus;
use App\Enums\FormTemplateContextType;
use App\Enums\FormTemplateFormatType;
use App\Enums\LogisticsStatus;
use App\Enums\SupplierConfirmationStatus;
use App\Enums\SupplierOrderStatus;
use App\Enums\UserRole;
use App\Jobs\RecalculateSupplyRiskJob;
use App\Models\AiEmailExtraction;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\EmailMessage;
use App\Models\FormAutofillFieldValue;
use App\Models\FormAutofillRun;
use App\Models\FormTemplate;
use App\Models\InboundOrder;
use App\Models\InboundOrderItem;
use App\Models\LogisticsRecord;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierConfirmation;
use App\Models\SupplierOrder;
use App\Models\SupplierOrderItem;
use App\Models\SupplierProductRule;
use App\Models\User;
use App\Services\Supply\SupplierConfirmationApplicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

function makeSupplierConfirmationApplicationFixture(bool $withSecondItem = false): array
{
    $company = Company::factory()->create(['name' => 'Confirmation Demo Co']);
    $supplier = Supplier::factory()->for($company)->create([
        'name' => 'Acme Manufacturing',
        'type' => 'manufacturer',
    ]);
    $product = Product::factory()->for($company)->create([
        'sku' => 'AX-150',
        'manufacturer_sku' => 'MFG-AX-150',
        'name' => 'Axle Bearing 150',
    ]);
    $secondProduct = Product::factory()->for($company)->create([
        'sku' => 'BRK-200',
        'manufacturer_sku' => 'MFG-BRK-200',
        'name' => 'Brake Kit 200',
    ]);

    SupplierProductRule::factory()->create([
        'supplier_id' => $supplier->getKey(),
        'product_id' => $product->getKey(),
        'supplier_sku' => 'SUP-AX-150',
    ]);
    SupplierProductRule::factory()->create([
        'supplier_id' => $supplier->getKey(),
        'product_id' => $secondProduct->getKey(),
        'supplier_sku' => 'SUP-BRK-200',
    ]);

    $supplierOrder = SupplierOrder::factory()->create([
        'company_id' => $company->getKey(),
        'supplier_id' => $supplier->getKey(),
        'order_proposal_id' => null,
        'order_number' => 'PO-CONF-1',
        'status' => SupplierOrderStatus::Sent,
        'order_date' => '2026-07-03',
    ]);
    $supplierOrderItem = SupplierOrderItem::factory()->create([
        'supplier_order_id' => $supplierOrder->getKey(),
        'product_id' => $product->getKey(),
        'ordered_quantity' => 156,
    ]);
    $secondSupplierOrderItem = null;

    if ($withSecondItem) {
        $secondSupplierOrderItem = SupplierOrderItem::factory()->create([
            'supplier_order_id' => $supplierOrder->getKey(),
            'product_id' => $secondProduct->getKey(),
            'ordered_quantity' => 24,
        ]);
    }

    $inboundOrder = InboundOrder::factory()->create([
        'company_id' => $company->getKey(),
        'supplier_id' => $supplier->getKey(),
        'order_number' => 'PO-CONF-1',
        'supplier_order_reference' => null,
        'status' => 'open',
        'ready_date' => null,
        'expected_arrival_date' => '2026-07-20',
        'confirmed_arrival_date' => null,
    ]);
    $inboundOrderItem = InboundOrderItem::factory()->create([
        'inbound_order_id' => $inboundOrder->getKey(),
        'product_id' => $product->getKey(),
        'ordered_quantity' => 156,
        'confirmed_quantity' => null,
        'expected_arrival_date' => '2026-07-20',
    ]);
    $logisticsRecord = LogisticsRecord::factory()->create([
        'company_id' => $company->getKey(),
        'supplier_order_id' => $supplierOrder->getKey(),
        'supplier_id' => $supplier->getKey(),
        'order_date' => '2026-07-03',
        'ready_date' => '2026-07-10',
        'delivery_date' => '2026-07-20',
        'status' => LogisticsStatus::Planned,
    ]);
    $user = User::factory()->create(['role' => UserRole::SupplyManager]);
    $email = EmailMessage::factory()->create([
        'company_id' => $company->getKey(),
        'direction' => EmailDirection::Inbound,
        'from_email' => 'orders@acme.test',
        'subject' => 'Confirmation PO-CONF-1',
        'body_text' => 'Confirmed.',
        'related_supplier_id' => $supplier->getKey(),
        'related_supplier_order_id' => $supplierOrder->getKey(),
        'status' => 'received',
    ]);

    return compact(
        'company',
        'supplier',
        'product',
        'secondProduct',
        'supplierOrder',
        'supplierOrderItem',
        'secondSupplierOrderItem',
        'inboundOrder',
        'inboundOrderItem',
        'logisticsRecord',
        'user',
        'email',
    );
}

function supplierConfirmationApplicationData(array $overrides = []): array
{
    $data = [
        'supplier_reference' => 'CONF-9001',
        'confirmation_date' => '2026-07-03',
        'ready_date' => '2026-07-10',
        'shipping_date' => '2026-07-11',
        'expected_arrival_date' => '2026-07-20',
        'items' => [
            [
                'supplier_sku' => 'SUP-AX-150',
                'confirmed_quantity' => 156,
            ],
        ],
    ];

    if (array_key_exists('items', $overrides)) {
        $data['items'] = $overrides['items'];
        unset($overrides['items']);
    }

    return array_replace_recursive($data, $overrides);
}

function applySupplierConfirmationApplication(array $fixture, array $data, array $overrides = []): array
{
    return app(SupplierConfirmationApplicationService::class)->apply(array_replace([
        'supplier_order_id' => $fixture['supplierOrder']->getKey(),
        'ai_email_extraction_id' => null,
        'form_autofill_run_id' => null,
        'manual_confirmation_data' => $data,
        'applied_by_user_id' => $fixture['user']->getKey(),
    ], $overrides));
}

function supplierConfirmationApplicationDiscrepancyTypes(SupplierConfirmation $confirmation): array
{
    return collect($confirmation->discrepancies_json ?: [])
        ->pluck('type')
        ->values()
        ->all();
}

it('updates an exact confirmation order to confirmed', function () {
    Queue::fake();
    $fixture = makeSupplierConfirmationApplicationFixture();

    $result = applySupplierConfirmationApplication($fixture, supplierConfirmationApplicationData());
    $confirmation = $result['confirmation'];

    expect($result['supplier_order']->status)->toBe(SupplierOrderStatus::Confirmed)
        ->and($confirmation->status)->toBe(SupplierConfirmationStatus::Confirmed)
        ->and($confirmation->items)->toHaveCount(1)
        ->and($confirmation->items->first()->status)->toBe('confirmed')
        ->and((float) $fixture['supplierOrderItem']->fresh()->confirmed_quantity)->toBe(156.0)
        ->and($fixture['inboundOrder']->fresh()->status)->toBe(SupplierOrderStatus::Confirmed->value)
        ->and((float) $fixture['inboundOrderItem']->fresh()->confirmed_quantity)->toBe(156.0)
        ->and($fixture['inboundOrder']->fresh()->confirmed_arrival_date?->toDateString())->toBe('2026-07-20');

    Queue::assertNotPushed(RecalculateSupplyRiskJob::class);
});

it('creates a lower quantity discrepancy', function () {
    Queue::fake();
    $fixture = makeSupplierConfirmationApplicationFixture();

    $result = applySupplierConfirmationApplication($fixture, supplierConfirmationApplicationData([
        'items' => [
            [
                'manufacturer_sku' => 'MFG-AX-150',
                'confirmed_quantity' => 120,
            ],
        ],
    ]));
    $confirmation = $result['confirmation'];

    expect($result['supplier_order']->status)->toBe(SupplierOrderStatus::PartiallyConfirmed)
        ->and($confirmation->status)->toBe(SupplierConfirmationStatus::QuantityMismatch)
        ->and($confirmation->items->first()->status)->toBe('partially_confirmed')
        ->and(supplierConfirmationApplicationDiscrepancyTypes($confirmation))->toContain('quantity_lower_than_ordered')
        ->and($result['risk_flagged'])->toBeTrue();

    Queue::assertNotPushed(RecalculateSupplyRiskJob::class);
});

it('requires human review for an unknown sku', function () {
    $fixture = makeSupplierConfirmationApplicationFixture();

    $result = applySupplierConfirmationApplication($fixture, supplierConfirmationApplicationData([
        'items' => [
            [
                'sku' => 'UNKNOWN-SKU',
                'product_name' => 'Axle Bearing 150',
                'confirmed_quantity' => 156,
            ],
        ],
    ]));
    $confirmation = $result['confirmation'];

    expect($result['supplier_order']->status)->toBe(SupplierOrderStatus::NeedsReview)
        ->and($confirmation->status)->toBe(SupplierConfirmationStatus::NeedsReview)
        ->and(supplierConfirmationApplicationDiscrepancyTypes($confirmation))->toContain('unknown_sku')
        ->and(supplierConfirmationApplicationDiscrepancyTypes($confirmation))->toContain('additional_item');
});

it('updates the logistics ready date', function () {
    $fixture = makeSupplierConfirmationApplicationFixture();
    $fixture['logisticsRecord']->forceFill(['ready_date' => null])->save();

    applySupplierConfirmationApplication($fixture, supplierConfirmationApplicationData([
        'ready_date' => '2026-07-12',
    ]));

    expect($fixture['logisticsRecord']->fresh()->ready_date?->toDateString())->toBe('2026-07-12');
});

it('dispatches risk recalculation when dates are delayed', function () {
    Queue::fake();
    $fixture = makeSupplierConfirmationApplicationFixture();
    $fixture['logisticsRecord']->forceFill([
        'ready_date' => '2026-07-08',
        'delivery_date' => '2026-07-18',
    ])->save();

    $result = applySupplierConfirmationApplication($fixture, supplierConfirmationApplicationData([
        'ready_date' => '2026-07-10',
        'expected_arrival_date' => '2026-07-20',
    ]));

    expect($result['supplier_order']->status)->toBe(SupplierOrderStatus::Delayed)
        ->and($result['risk_flagged'])->toBeTrue();

    Queue::assertNotPushed(RecalculateSupplyRiskJob::class);
});

it('writes an audit log', function () {
    $fixture = makeSupplierConfirmationApplicationFixture();

    $result = applySupplierConfirmationApplication($fixture, supplierConfirmationApplicationData());

    expect(AuditLog::query()
        ->where('event_type', 'supplier_confirmation_applied')
        ->where('auditable_id', $result['confirmation']->getKey())
        ->where('user_id', $fixture['user']->getKey())
        ->exists())->toBeTrue();
});

it('applies manual confirmation data without an AI extraction', function () {
    $fixture = makeSupplierConfirmationApplicationFixture();

    $result = applySupplierConfirmationApplication($fixture, supplierConfirmationApplicationData());
    $confirmation = $result['confirmation'];

    expect($confirmation->created_from_ai_extraction_id)->toBeNull()
        ->and($confirmation->created_from_form_autofill_run_id)->toBeNull()
        ->and($confirmation->supplier_reference)->toBe('CONF-9001');
});

it('applies a validated form autofill confirmation', function () {
    $fixture = makeSupplierConfirmationApplicationFixture();
    $template = FormTemplate::factory()->create([
        'company_id' => $fixture['company']->getKey(),
        'supplier_id' => $fixture['supplier']->getKey(),
        'context_type' => FormTemplateContextType::SupplierConfirmation,
        'format_type' => FormTemplateFormatType::InternalHtml,
    ]);
    $formRun = FormAutofillRun::factory()->create([
        'company_id' => $fixture['company']->getKey(),
        'email_message_id' => $fixture['email']->getKey(),
        'form_template_id' => $template->getKey(),
        'ai_email_extraction_id' => AiEmailExtraction::factory()->create([
            'email_message_id' => $fixture['email']->getKey(),
        ])->getKey(),
        'status' => FormAutofillRunStatus::Validated,
        'created_by_user_id' => $fixture['user']->getKey(),
    ]);

    foreach ([
        'supplier_reference' => 'FORM-CONF-1',
        'supplier_order_number' => 'PO-CONF-1',
        'sku' => 'AX-150',
        'confirmed_quantity' => '156',
        'confirmation_date' => '2026-07-03',
        'ready_date' => '2026-07-10',
        'shipping_date' => '2026-07-11',
        'expected_arrival_date' => '2026-07-20',
    ] as $fieldKey => $value) {
        FormAutofillFieldValue::factory()->create([
            'form_autofill_run_id' => $formRun->getKey(),
            'field_key' => $fieldKey,
            'final_value' => $value,
            'requires_review' => false,
        ]);
    }

    $result = applySupplierConfirmationApplication($fixture, [], [
        'form_autofill_run_id' => $formRun->getKey(),
    ]);
    $confirmation = $result['confirmation'];

    expect($result['supplier_order']->status)->toBe(SupplierOrderStatus::Confirmed)
        ->and($confirmation->created_from_form_autofill_run_id)->toBe($formRun->getKey())
        ->and($confirmation->supplier_reference)->toBe('FORM-CONF-1');
});
