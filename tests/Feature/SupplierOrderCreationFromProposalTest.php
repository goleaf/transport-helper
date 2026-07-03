<?php

use App\Enums\OrderProposalItemStatus;
use App\Enums\OrderProposalStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\CalculationRun;
use App\Models\Company;
use App\Models\EmailMessage;
use App\Models\ExportFile;
use App\Models\LogisticsRecord;
use App\Models\OrderProposal;
use App\Models\OrderProposalItem;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierOrder;
use App\Models\SupplierOrderItem;
use App\Models\User;
use App\Services\Supply\OrderProposals\SupplierOrderCreationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

afterEach(function (): void {
    Carbon::setTestNow();
});

function stage4ConversionFixture(array $proposalOverrides = []): array
{
    Carbon::setTestNow('2026-07-03 09:00:00');

    $company = Company::factory()->create();
    $supplier = Supplier::factory()->for($company)->create();
    $run = CalculationRun::factory()->for($company)->for($supplier)->create();
    $user = User::factory()->create(['role' => UserRole::SupplyManager]);
    $proposal = OrderProposal::factory()
        ->for($company)
        ->for($supplier)
        ->for($run, 'calculationRun')
        ->create(array_merge([
            'status' => OrderProposalStatus::Approved,
        ], $proposalOverrides));

    $products = Product::factory()->for($company)->count(4)->create();

    OrderProposalItem::factory()->for($proposal, 'orderProposal')->for($products[0])->create([
        'status' => OrderProposalItemStatus::Approved,
        'approved_quantity' => 156,
    ]);
    OrderProposalItem::factory()->for($proposal, 'orderProposal')->for($products[1])->create([
        'status' => OrderProposalItemStatus::Adjusted,
        'approved_quantity' => 24,
    ]);
    OrderProposalItem::factory()->for($proposal, 'orderProposal')->for($products[2])->create([
        'status' => OrderProposalItemStatus::Rejected,
        'approved_quantity' => null,
    ]);
    OrderProposalItem::factory()->for($proposal, 'orderProposal')->for($products[3])->create([
        'status' => OrderProposalItemStatus::Adjusted,
        'approved_quantity' => 0,
    ]);

    return compact('company', 'supplier', 'run', 'user', 'proposal', 'products');
}

it('creates supplier order from approved proposal and excludes rejected or zero lines', function () {
    $fixture = stage4ConversionFixture();

    $result = app(SupplierOrderCreationService::class)
        ->createFromApprovedProposal($fixture['proposal'], $fixture['user']);

    $supplierOrder = $result['supplier_order'];

    expect(SupplierOrder::query()->count())->toBe(1)
        ->and(SupplierOrderItem::query()->count())->toBe(2)
        ->and($supplierOrder->order_number)->toStartWith('PO-20260703-')
        ->and($fixture['proposal']->fresh()->status)->toBe(OrderProposalStatus::ConvertedToSupplierOrder)
        ->and(AuditLog::query()->where('event_type', 'supplier_order_created')->exists())->toBeTrue()
        ->and(AuditLog::query()->where('event_type', 'order_proposal_converted_to_supplier_order')->exists())->toBeTrue();
});

it('cannot convert an unapproved proposal', function () {
    $fixture = stage4ConversionFixture(['status' => OrderProposalStatus::Draft]);

    app(SupplierOrderCreationService::class)
        ->createFromApprovedProposal($fixture['proposal'], $fixture['user']);
})->throws(ValidationException::class);

it('cannot convert a proposal twice', function () {
    $fixture = stage4ConversionFixture();

    app(SupplierOrderCreationService::class)
        ->createFromApprovedProposal($fixture['proposal'], $fixture['user']);

    app(SupplierOrderCreationService::class)
        ->createFromApprovedProposal($fixture['proposal']->fresh(), $fixture['user']);
})->throws(ValidationException::class);

it('creates a planned logistics record during conversion', function () {
    $fixture = stage4ConversionFixture();

    app(SupplierOrderCreationService::class)
        ->createFromApprovedProposal($fixture['proposal'], $fixture['user']);

    expect(LogisticsRecord::query()->count())->toBe(1)
        ->and(LogisticsRecord::query()->first()->status->value)->toBe('planned')
        ->and(AuditLog::query()->where('event_type', 'logistics_record_created')->exists())->toBeTrue();
});

it('does not export files or create email messages during conversion', function () {
    $fixture = stage4ConversionFixture();

    app(SupplierOrderCreationService::class)
        ->createFromApprovedProposal($fixture['proposal'], $fixture['user']);

    expect(ExportFile::query()->count())->toBe(0)
        ->and(EmailMessage::query()->count())->toBe(0);
});
