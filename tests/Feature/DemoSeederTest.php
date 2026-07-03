<?php

use App\Models\AppSetting;
use App\Models\AuditLog;
use App\Models\Carrier;
use App\Models\CarrierQuote;
use App\Models\Company;
use App\Models\EmailAttachment;
use App\Models\EmailMessage;
use App\Models\ExportFile;
use App\Models\FormAutofillRun;
use App\Models\FormTemplate;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\InboundOrder;
use App\Models\IntegrationConnection;
use App\Models\LogisticsRecord;
use App\Models\OrderProposal;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\Role;
use App\Models\SalesHistory;
use App\Models\SavedView;
use App\Models\StockSnapshot;
use App\Models\Supplier;
use App\Models\SupplierConfirmation;
use App\Models\SupplierOrder;
use App\Models\SupplierProductRule;
use App\Models\UserPreference;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds demo supply data and remains idempotent', function () {
    $this->seed(DatabaseSeeder::class);

    $countsAfterFirstRun = [
        'roles' => Role::query()->count(),
        'templates' => FormTemplate::query()->count(),
        'products' => Product::query()->count(),
    ];

    $this->seed(DatabaseSeeder::class);

    $company = Company::query()->where('code', 'DEMO')->firstOrFail();

    expect($company->name)->toBe('Demo Supply Company')
        ->and(Supplier::query()->where('company_id', $company->getKey())->where('name', 'Demo Manufacturer')->exists())->toBeTrue()
        ->and(Carrier::query()->where('company_id', $company->getKey())->where('name', 'Demo Carrier A')->exists())->toBeTrue()
        ->and(Product::query()->where('company_id', $company->getKey())->count())->toBeGreaterThanOrEqual(5)
        ->and(FormTemplate::query()->where('company_id', $company->getKey())->where('code', 'supplier_confirmation_v1')->exists())->toBeTrue()
        ->and(FormTemplate::query()->where('company_id', $company->getKey())->where('code', 'carrier_quote_v1')->exists())->toBeTrue()
        ->and(FormTemplate::query()->where('company_id', $company->getKey())->where('code', 'logistics_update_v1')->exists())->toBeTrue()
        ->and(FormTemplate::query()->where('code', 'supplier_confirmation_v1')->firstOrFail()->fields()->where('field_key', 'confirmed_quantity')->exists())->toBeTrue()
        ->and($countsAfterFirstRun['roles'])->toBe(Role::query()->count())
        ->and($countsAfterFirstRun['templates'])->toBe(FormTemplate::query()->count())
        ->and($countsAfterFirstRun['products'])->toBe(Product::query()->count());
});

it('seeds a relation-rich procurement operations lab and remains idempotent', function () {
    $this->seed(DatabaseSeeder::class);

    $company = Company::query()->where('code', 'DEMO-LARGE')->firstOrFail();
    $countsAfterFirstRun = [
        'supplier_orders' => SupplierOrder::query()->whereBelongsTo($company)->count(),
        'email_messages' => EmailMessage::query()->whereBelongsTo($company)->count(),
        'carrier_quotes' => CarrierQuote::query()->whereBelongsTo($company)->count(),
        'logistics_records' => LogisticsRecord::query()->whereBelongsTo($company)->count(),
    ];

    $this->seed(DatabaseSeeder::class);

    expect(Supplier::query()->whereBelongsTo($company)->count())->toBeGreaterThanOrEqual(6)
        ->and(Carrier::query()->whereBelongsTo($company)->count())->toBeGreaterThanOrEqual(5)
        ->and(Product::query()->whereBelongsTo($company)->count())->toBeGreaterThanOrEqual(30)
        ->and(SupplierProductRule::query()->whereHas('supplier', fn ($query) => $query->whereBelongsTo($company))->count())->toBeGreaterThanOrEqual(60)
        ->and(StockSnapshot::query()->whereBelongsTo($company)->count())->toBeGreaterThanOrEqual(120)
        ->and(SalesHistory::query()->whereBelongsTo($company)->count())->toBeGreaterThanOrEqual(300)
        ->and(Reservation::query()->whereBelongsTo($company)->count())->toBeGreaterThanOrEqual(15)
        ->and(ImportBatch::query()->whereBelongsTo($company)->count())->toBeGreaterThanOrEqual(4)
        ->and(ImportRow::query()->whereHas('importBatch', fn ($query) => $query->whereBelongsTo($company))->count())->toBeGreaterThanOrEqual(48)
        ->and(InboundOrder::query()->whereBelongsTo($company)->count())->toBeGreaterThanOrEqual(8)
        ->and(OrderProposal::query()->whereBelongsTo($company)->count())->toBeGreaterThanOrEqual(8)
        ->and(SupplierOrder::query()->whereBelongsTo($company)->count())->toBe($countsAfterFirstRun['supplier_orders'])
        ->and(EmailMessage::query()->whereBelongsTo($company)->count())->toBe($countsAfterFirstRun['email_messages'])
        ->and(EmailAttachment::query()->whereHas('emailMessage', fn ($query) => $query->whereBelongsTo($company))->count())->toBeGreaterThanOrEqual(16)
        ->and(FormAutofillRun::query()->whereBelongsTo($company)->count())->toBeGreaterThanOrEqual(48)
        ->and(SupplierConfirmation::query()->whereBelongsTo($company)->count())->toBeGreaterThanOrEqual(8)
        ->and(CarrierQuote::query()->whereBelongsTo($company)->count())->toBe($countsAfterFirstRun['carrier_quotes'])
        ->and(LogisticsRecord::query()->whereBelongsTo($company)->count())->toBe($countsAfterFirstRun['logistics_records'])
        ->and(AuditLog::query()->whereBelongsTo($company)->count())->toBeGreaterThanOrEqual(50)
        ->and(ExportFile::query()->whereBelongsTo($company)->count())->toBeGreaterThanOrEqual(8)
        ->and(IntegrationConnection::query()->whereBelongsTo($company)->count())->toBeGreaterThanOrEqual(4)
        ->and(AppSetting::query()->whereBelongsTo($company)->count())->toBeGreaterThanOrEqual(6)
        ->and(SavedView::query()->whereBelongsTo($company)->count())->toBeGreaterThanOrEqual(5)
        ->and(UserPreference::query()->where('key', 'supply.dashboard.layout')->count())->toBeGreaterThanOrEqual(5);

    $connectedOrder = SupplierOrder::query()
        ->whereBelongsTo($company)
        ->whereHas('items')
        ->whereHas('emailMessages.aiEmailExtractions')
        ->whereHas('confirmations.items')
        ->whereHas('carrierQuotes')
        ->whereHas('logisticsRecords')
        ->first();

    expect($connectedOrder)->not->toBeNull();
});
