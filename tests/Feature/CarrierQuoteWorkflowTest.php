<?php

use App\Enums\CarrierQuoteStatus;
use App\Enums\LogisticsStatus;
use App\Enums\SupplierOrderStatus;
use App\Enums\UserRole;
use App\Models\AiEmailExtraction;
use App\Models\AuditLog;
use App\Models\Carrier;
use App\Models\CarrierQuote;
use App\Models\Company;
use App\Models\EmailMessage;
use App\Models\FormAutofillRun;
use App\Models\FormTemplate;
use App\Models\LogisticsRecord;
use App\Models\Supplier;
use App\Models\SupplierOrder;
use App\Models\User;
use App\Services\Supply\CarrierQuoteApplicationService;
use App\Services\Supply\CarrierQuoteScoringService;
use App\Services\Supply\CarrierSelectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeCarrierQuoteWorkflowFixture(): array
{
    $company = Company::factory()->create(['default_currency' => 'EUR']);
    $supplier = Supplier::factory()->for($company)->create([
        'type' => 'manufacturer',
        'default_currency' => 'EUR',
    ]);
    $supplierOrder = SupplierOrder::factory()->create([
        'company_id' => $company->getKey(),
        'supplier_id' => $supplier->getKey(),
        'order_proposal_id' => null,
        'order_number' => 'PO-CARRIER-1',
        'status' => SupplierOrderStatus::Sent,
        'order_date' => '2026-07-03',
    ]);
    $carrier = Carrier::factory()->for($company)->create([
        'name' => 'Express Road',
        'default_currency' => 'EUR',
        'reliability_score' => 94.00,
    ]);
    $lateCarrier = Carrier::factory()->for($company)->create([
        'name' => 'Cheap Late Transport',
        'default_currency' => 'EUR',
        'reliability_score' => 70.00,
    ]);
    $logisticsRecord = LogisticsRecord::factory()->create([
        'company_id' => $company->getKey(),
        'supplier_order_id' => $supplierOrder->getKey(),
        'supplier_id' => $supplier->getKey(),
        'carrier_id' => null,
        'pickup_date' => '2026-07-10',
        'delivery_date' => '2026-07-20',
        'status' => LogisticsStatus::Planned,
    ]);
    $user = User::factory()->create(['role' => UserRole::LogisticsManager]);
    $email = EmailMessage::factory()->create([
        'company_id' => $company->getKey(),
        'related_supplier_id' => $supplier->getKey(),
        'related_supplier_order_id' => $supplierOrder->getKey(),
        'status' => 'received',
    ]);

    return compact('company', 'supplier', 'supplierOrder', 'carrier', 'lateCarrier', 'logisticsRecord', 'user', 'email');
}

function createCarrierQuoteForWorkflow(array $fixture, array $overrides = []): CarrierQuote
{
    $result = app(CarrierQuoteApplicationService::class)->create(array_replace([
        'supplier_order_id' => $fixture['supplierOrder']->getKey(),
        'carrier_id' => $fixture['carrier']->getKey(),
        'price' => 430.50,
        'currency' => 'EUR',
        'pickup_date' => '2026-07-10',
        'delivery_date' => '2026-07-20',
        'transit_days' => 10,
        'conditions' => 'Standard road freight.',
        'required_pickup_date' => '2026-07-10',
        'required_delivery_date' => '2026-07-20',
        'source_type' => 'manual',
        'created_by_user_id' => $fixture['user']->getKey(),
    ], $overrides));

    return $result['quote'];
}

it('does not always choose a lower price when the delivery date is late', function () {
    $scoring = app(CarrierQuoteScoringService::class);

    $cheapLate = $scoring->score([
        'price' => 100,
        'currency' => 'EUR',
        'pickup_date' => '2026-07-10',
        'delivery_date' => '2026-07-25',
        'transit_days' => 15,
        'reliability_score' => 70,
        'conditions' => null,
        'required_pickup_date' => '2026-07-10',
        'required_delivery_date' => '2026-07-20',
    ]);
    $expensiveOnTime = $scoring->score([
        'price' => 300,
        'currency' => 'EUR',
        'pickup_date' => '2026-07-10',
        'delivery_date' => '2026-07-20',
        'transit_days' => 10,
        'reliability_score' => 95,
        'conditions' => null,
        'required_pickup_date' => '2026-07-10',
        'required_delivery_date' => '2026-07-20',
    ]);

    expect($expensiveOnTime['calculated_score'])->toBeGreaterThan($cheapLate['calculated_score'])
        ->and(collect($cheapLate['explanation']['penalties'])->pluck('type')->all())->toContain('penalty_late_delivery');
});

it('marks a quote with a missing delivery date as needing review', function () {
    $fixture = makeCarrierQuoteWorkflowFixture();

    $quote = createCarrierQuoteForWorkflow($fixture, [
        'delivery_date' => null,
    ]);

    expect($quote->status)->toBe(CarrierQuoteStatus::NeedsReview)
        ->and($quote->score_explanation_json['requires_human_review'])->toBeTrue()
        ->and($quote->score_explanation_json['warnings'])->toContain('missing_delivery_date');
});

it('updates the logistics record when a user selects a quote', function () {
    $fixture = makeCarrierQuoteWorkflowFixture();
    $quote = createCarrierQuoteForWorkflow($fixture);

    $result = app(CarrierSelectionService::class)->select($quote, $fixture['user']);
    $logisticsRecord = $result['logistics_record'];

    expect($result['quote']->status)->toBe(CarrierQuoteStatus::Selected)
        ->and($logisticsRecord->carrier_id)->toBe($fixture['carrier']->getKey())
        ->and($logisticsRecord->pickup_date?->toDateString())->toBe('2026-07-10')
        ->and($logisticsRecord->delivery_date?->toDateString())->toBe('2026-07-20')
        ->and((float) $logisticsRecord->transport_price)->toBe(430.5)
        ->and($logisticsRecord->status)->toBe(LogisticsStatus::PickupScheduled);
});

it('writes an audit log when a quote is selected', function () {
    $fixture = makeCarrierQuoteWorkflowFixture();
    $quote = createCarrierQuoteForWorkflow($fixture);

    app(CarrierSelectionService::class)->select($quote, $fixture['user']);

    expect(AuditLog::query()
        ->where('event_type', 'carrier_quote.selected')
        ->where('auditable_id', $quote->getKey())
        ->where('user_id', $fixture['user']->getKey())
        ->exists())->toBeTrue();
});

it('does not automatically select an AI-created quote', function () {
    $fixture = makeCarrierQuoteWorkflowFixture();
    $extraction = AiEmailExtraction::factory()->create([
        'email_message_id' => $fixture['email']->getKey(),
    ]);

    $quote = createCarrierQuoteForWorkflow($fixture, [
        'ai_email_extraction_id' => $extraction->getKey(),
        'source_type' => 'ai_email_extraction',
        'created_by_user_id' => null,
    ]);

    expect($quote->created_from_ai_extraction_id)->toBe($extraction->getKey())
        ->and($quote->status)->not->toBe(CarrierQuoteStatus::Selected);
});

it('does not automatically select a form-autofill-created quote', function () {
    $fixture = makeCarrierQuoteWorkflowFixture();
    $template = FormTemplate::factory()->create([
        'company_id' => $fixture['company']->getKey(),
    ]);
    $formRun = FormAutofillRun::factory()->create([
        'company_id' => $fixture['company']->getKey(),
        'email_message_id' => $fixture['email']->getKey(),
        'form_template_id' => $template->getKey(),
    ]);

    $quote = createCarrierQuoteForWorkflow($fixture, [
        'form_autofill_run_id' => $formRun->getKey(),
        'source_type' => 'form_autofill',
    ]);

    expect($quote->created_from_form_autofill_run_id)->toBe($formRun->getKey())
        ->and($quote->status)->not->toBe(CarrierQuoteStatus::Selected);
});

it('stores a manual carrier quote from the route', function () {
    $fixture = makeCarrierQuoteWorkflowFixture();

    $response = $this->actingAs($fixture['user'])->post(route('supply.transport.quotes.manual'), [
        'supplier_order_id' => $fixture['supplierOrder']->getKey(),
        'carrier_id' => $fixture['carrier']->getKey(),
        'price' => 500,
        'currency' => 'EUR',
        'pickup_date' => '2026-07-10',
        'delivery_date' => '2026-07-20',
        'transit_days' => 10,
        'conditions' => 'Manual quote entry.',
    ]);

    $response->assertRedirectToRoute('supply.transport.orders.quotes.index', $fixture['supplierOrder']);

    $quote = CarrierQuote::query()->firstOrFail();

    expect((float) $quote->price)->toBe(500.0)
        ->and($quote->status)->toBe(CarrierQuoteStatus::Received)
        ->and($quote->created_from_ai_extraction_id)->toBeNull()
        ->and($quote->created_from_form_autofill_run_id)->toBeNull()
        ->and(AuditLog::query()->where('event_type', 'carrier_quote.created')->exists())->toBeTrue();
});
