<?php

use App\Enums\CarrierQuoteStatus;
use App\Enums\LogisticsStatus;
use App\Enums\OrderProposalItemStatus;
use App\Enums\OrderProposalStatus;
use App\Enums\SupplierConfirmationStatus;
use App\Enums\SupplierOrderStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\CalculationRun;
use App\Models\Carrier;
use App\Models\CarrierQuote;
use App\Models\Company;
use App\Models\LogisticsRecord;
use App\Models\OrderProposal;
use App\Models\OrderProposalItem;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierConfirmation;
use App\Models\SupplierConfirmationItem;
use App\Models\SupplierOrder;
use App\Models\SupplierOrderItem;
use App\Models\User;
use App\Services\Supply\CarrierSelectionService;
use App\Services\Supply\LogisticsExportService;
use App\Services\Supply\LogisticsGoogleSheetsSyncService;
use App\Services\Supply\LogisticsNotificationService;
use App\Services\Supply\LogisticsRecordService;
use App\Services\Supply\SupplierOrderCreationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function makeLogisticsSupplierOrderCreationFixture(): array
{
    $company = Company::factory()->create(['default_currency' => 'EUR']);
    $supplier = Supplier::factory()->for($company)->create([
        'type' => 'manufacturer',
        'default_currency' => 'EUR',
    ]);
    $product = Product::factory()->for($company)->create([
        'sku' => 'LOG-150',
        'name' => 'Logistics Test Product',
    ]);
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
        'created_by_user_id' => $user->getKey(),
        'status' => OrderProposalStatus::Approved,
        'total_lines' => 1,
    ]);

    OrderProposalItem::factory()->create([
        'order_proposal_id' => $proposal->getKey(),
        'product_id' => $product->getKey(),
        'status' => OrderProposalItemStatus::Approved,
        'approved_quantity' => 156,
        'recommended_quantity' => 156,
        'requires_human_review' => false,
    ]);

    return compact('company', 'supplier', 'product', 'user', 'calculationRun', 'proposal');
}

function makeLogisticsWorkflowFixture(): array
{
    $company = Company::factory()->create(['default_currency' => 'EUR']);
    $supplier = Supplier::factory()->for($company)->create([
        'type' => 'manufacturer',
        'default_currency' => 'EUR',
    ]);
    $product = Product::factory()->for($company)->create([
        'sku' => 'LOG-200',
        'name' => 'Logistics Flow Product',
    ]);
    $supplierOrder = SupplierOrder::factory()->create([
        'company_id' => $company->getKey(),
        'supplier_id' => $supplier->getKey(),
        'order_proposal_id' => null,
        'order_number' => 'PO-LOG-1',
        'status' => SupplierOrderStatus::Sent,
        'order_date' => '2026-07-03',
    ]);
    $supplierOrderItem = SupplierOrderItem::factory()->create([
        'supplier_order_id' => $supplierOrder->getKey(),
        'product_id' => $product->getKey(),
        'ordered_quantity' => 156,
        'status' => 'ordered',
    ]);
    $logisticsRecord = LogisticsRecord::factory()->create([
        'company_id' => $company->getKey(),
        'supplier_order_id' => $supplierOrder->getKey(),
        'supplier_id' => $supplier->getKey(),
        'carrier_id' => null,
        'order_date' => '2026-07-03',
        'confirmation_date' => null,
        'ready_date' => '2026-07-10',
        'pickup_date' => null,
        'delivery_date' => '2026-07-20',
        'transport_price' => null,
        'currency' => 'EUR',
        'status' => LogisticsStatus::Planned,
    ]);
    $carrier = Carrier::factory()->for($company)->create([
        'name' => 'Reliable Road',
        'default_currency' => 'EUR',
        'reliability_score' => 92,
    ]);
    $user = User::factory()->create(['role' => UserRole::LogisticsManager]);

    return compact('company', 'supplier', 'product', 'supplierOrder', 'supplierOrderItem', 'logisticsRecord', 'carrier', 'user');
}

function makeLogisticsSupplierConfirmation(array $fixture, array $overrides = [], array $itemOverrides = []): SupplierConfirmation
{
    $confirmation = SupplierConfirmation::factory()->create(array_replace([
        'company_id' => $fixture['company']->getKey(),
        'supplier_order_id' => $fixture['supplierOrder']->getKey(),
        'email_message_id' => null,
        'supplier_reference' => 'CONF-LOG-1',
        'confirmation_date' => '2026-07-04',
        'ready_date' => '2026-07-11',
        'shipping_date' => '2026-07-12',
        'expected_arrival_date' => '2026-07-21',
        'status' => SupplierConfirmationStatus::Confirmed,
        'created_from_ai_extraction_id' => null,
        'created_from_form_autofill_run_id' => null,
    ], $overrides));

    SupplierConfirmationItem::factory()->create(array_replace([
        'supplier_confirmation_id' => $confirmation->getKey(),
        'product_id' => $fixture['product']->getKey(),
        'ordered_quantity' => 156,
        'confirmed_quantity' => 156,
        'discrepancy_quantity' => 0,
        'status' => 'confirmed',
    ], $itemOverrides));

    return $confirmation;
}

it('creates a logistics record when a supplier order is created', function () {
    $fixture = makeLogisticsSupplierOrderCreationFixture();

    $supplierOrder = app(SupplierOrderCreationService::class)
        ->createFromApprovedProposal($fixture['proposal'], $fixture['user']);

    $record = LogisticsRecord::query()
        ->whereBelongsTo($supplierOrder)
        ->first();

    expect($record)->not->toBeNull()
        ->and($record->supplier_id)->toBe($fixture['supplier']->getKey())
        ->and($record->status)->toBe(LogisticsStatus::Planned)
        ->and(AuditLog::query()->where('event_type', 'logistics_record_created')->exists())->toBeTrue();
});

it('updates logistics dates from supplier confirmation', function () {
    $fixture = makeLogisticsWorkflowFixture();
    $confirmation = makeLogisticsSupplierConfirmation($fixture);

    $result = app(LogisticsRecordService::class)
        ->updateFromSupplierConfirmation($confirmation, $fixture['user']);
    $record = $result['record'];

    expect($record->confirmation_date?->toDateString())->toBe('2026-07-04')
        ->and($record->ready_date?->toDateString())->toBe('2026-07-11')
        ->and($record->pickup_date?->toDateString())->toBe('2026-07-12')
        ->and($record->delivery_date?->toDateString())->toBe('2026-07-21')
        ->and($record->status)->toBe(LogisticsStatus::Confirmed)
        ->and($result['notifications'])->toContain(LogisticsNotificationService::SupplierConfirmationReceived);
});

it('updates carrier and price when a carrier is selected', function () {
    $fixture = makeLogisticsWorkflowFixture();
    $quote = CarrierQuote::factory()->create([
        'company_id' => $fixture['company']->getKey(),
        'supplier_order_id' => $fixture['supplierOrder']->getKey(),
        'carrier_id' => $fixture['carrier']->getKey(),
        'email_message_id' => null,
        'price' => 410.25,
        'currency' => 'EUR',
        'pickup_date' => '2026-07-12',
        'delivery_date' => '2026-07-21',
        'status' => CarrierQuoteStatus::Received,
        'created_from_ai_extraction_id' => null,
        'created_from_form_autofill_run_id' => null,
    ]);

    $result = app(CarrierSelectionService::class)->select($quote, $fixture['user']);
    $record = $result['logistics_record'];

    expect($record->carrier_id)->toBe($fixture['carrier']->getKey())
        ->and((float) $record->transport_price)->toBe(410.25)
        ->and($record->pickup_date?->toDateString())->toBe('2026-07-12')
        ->and($record->delivery_date?->toDateString())->toBe('2026-07-21')
        ->and($record->status)->toBe(LogisticsStatus::PickupScheduled);
});

it('sends a database notification when a supplier confirmation delays dates', function () {
    $fixture = makeLogisticsWorkflowFixture();
    $confirmation = makeLogisticsSupplierConfirmation($fixture, [
        'ready_date' => '2026-07-15',
        'expected_arrival_date' => '2026-07-25',
    ]);

    app(LogisticsRecordService::class)
        ->updateFromSupplierConfirmation($confirmation, $fixture['user']);

    $eventTypes = $fixture['user']
        ->notifications()
        ->get()
        ->map(fn ($notification): ?string => $notification->data['event_type'] ?? null)
        ->all();

    expect($eventTypes)->toContain(LogisticsNotificationService::DateDelay);
});

it('exports logistics records to CSV', function () {
    Storage::fake(config('filesystems.default'));
    $fixture = makeLogisticsWorkflowFixture();

    $result = app(LogisticsExportService::class)->exportCsv([
        'company_id' => $fixture['company']->getKey(),
    ], $fixture['user']);

    expect(Storage::exists($result['path']))->toBeTrue()
        ->and($result['content'])->toContain('supplier_order')
        ->and($result['content'])->toContain('PO-LOG-1')
        ->and($result['content'])->toContain('planned')
        ->and($result['row_count'])->toBe(1);
});

it('writes an audit log when logistics status is updated manually', function () {
    $fixture = makeLogisticsWorkflowFixture();

    $response = $this->actingAs($fixture['user'])
        ->post(route('supply.logistics.update-status', $fixture['logisticsRecord']), [
            'status' => LogisticsStatus::Delayed->value,
            'reason' => 'Supplier delivery is late.',
        ]);

    $response->assertRedirect(route('supply.logistics.show', $fixture['logisticsRecord']));

    expect($fixture['logisticsRecord']->fresh()->status)->toBe(LogisticsStatus::Delayed)
        ->and(AuditLog::query()
            ->where('event_type', 'logistics_status_changed')
            ->where('auditable_id', $fixture['logisticsRecord']->getKey())
            ->where('user_id', $fixture['user']->getKey())
            ->exists())->toBeTrue();
});

it('runs google sheets logistics sync as dry run by default', function () {
    $result = app(LogisticsGoogleSheetsSyncService::class)->sync();

    expect($result['dry_run'])->toBeTrue()
        ->and($result['provider_result'])->toBeNull();
});

it('protects logistics pages from guests', function () {
    $fixture = makeLogisticsWorkflowFixture();

    $this->get(route('supply.logistics.index'))
        ->assertRedirect(route('login'));

    $this->get(route('supply.logistics.show', $fixture['logisticsRecord']))
        ->assertRedirect(route('login'));
});

it('allows authenticated users to view logistics pages', function () {
    $fixture = makeLogisticsWorkflowFixture();
    $viewer = User::factory()->create(['role' => UserRole::Viewer]);

    $this->actingAs($viewer)
        ->get(route('supply.logistics.index'))
        ->assertOk()
        ->assertSeeText('PO-LOG-1');

    $this->actingAs($viewer)
        ->get(route('supply.logistics.show', $fixture['logisticsRecord']))
        ->assertOk()
        ->assertSeeText('PO-LOG-1');
});

it('prevents viewers from changing logistics records', function () {
    $fixture = makeLogisticsWorkflowFixture();
    $viewer = User::factory()->create(['role' => UserRole::Viewer]);

    $this->actingAs($viewer)
        ->post(route('supply.logistics.update-status', $fixture['logisticsRecord']), [
            'status' => LogisticsStatus::Delayed->value,
            'reason' => 'Attempted viewer update.',
        ])
        ->assertForbidden();

    $this->actingAs($viewer)
        ->post(route('supply.logistics.sync.google-sheets'))
        ->assertForbidden();

    expect($fixture['logisticsRecord']->fresh()->status)->toBe(LogisticsStatus::Planned);
});
