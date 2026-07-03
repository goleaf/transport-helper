<?php

use App\Enums\UserRole;
use App\Models\AiEmailExtraction;
use App\Models\CalculationRun;
use App\Models\Carrier;
use App\Models\CarrierQuote;
use App\Models\Company;
use App\Models\EmailAccount;
use App\Models\EmailMessage;
use App\Models\FormTemplate;
use App\Models\LogisticsRecord;
use App\Models\OrderProposal;
use App\Models\OrderProposalItem;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\SupplierConfirmation;
use App\Models\SupplierConfirmationItem;
use App\Models\SupplierOrder;
use App\Models\SupplierOrderItem;
use App\Models\SupplierProductRule;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('migrates the procurement domain schema requested by the workflow', function () {
    $tables = [
        'companies',
        'suppliers',
        'supplier_contacts',
        'products',
        'supplier_product_rules',
        'stock_snapshots',
        'sales_history',
        'inbound_orders',
        'inbound_order_items',
        'reservations',
        'calculation_runs',
        'order_proposals',
        'order_proposal_items',
        'supplier_orders',
        'supplier_order_items',
        'email_accounts',
        'email_messages',
        'email_attachments',
        'ai_email_extractions',
        'supplier_confirmations',
        'supplier_confirmation_items',
        'carriers',
        'carrier_contacts',
        'carrier_quotes',
        'logistics_records',
        'import_batches',
        'import_rows',
        'export_files',
        'integration_connections',
        'app_settings',
        'audit_logs',
        'roles',
        'permissions',
        'permission_role',
        'role_user',
    ];

    foreach ($tables as $table) {
        expect(Schema::hasTable($table))->toBeTrue();
    }

    expect(Schema::hasColumns('order_proposal_items', [
        't0_date',
        't1_date',
        't2_date',
        't3_date',
        'raw_need',
        'recommended_quantity',
        'explanation_json',
        'requires_human_review',
    ]))->toBeTrue()
        ->and(Schema::hasColumns('email_messages', [
            'direction',
            'message_id',
            'thread_id',
            'to_json',
            'cc_json',
            'related_supplier_id',
            'related_supplier_order_id',
        ]))->toBeTrue()
        ->and(Schema::hasColumns('carrier_quotes', [
            'price',
            'pickup_date',
            'delivery_date',
            'calculated_score',
            'score_explanation_json',
        ]))->toBeTrue()
        ->and(Schema::hasColumns('audit_logs', [
            'event_type',
            'auditable_type',
            'auditable_id',
            'old_values_json',
            'new_values_json',
        ]))->toBeTrue();
});

it('enforces company sku and supplier product rule uniqueness', function () {
    $company = Company::factory()->create();
    $supplier = Supplier::factory()->for($company)->create();
    $product = Product::factory()->for($company)->create(['sku' => 'AX-150']);

    Product::factory()->for($company)->create(['sku' => 'UNIQUE-1']);

    expect(fn () => Product::factory()->for($company)->create(['sku' => 'AX-150']))
        ->toThrow(QueryException::class);

    SupplierProductRule::factory()
        ->for($supplier)
        ->for($product)
        ->create();

    expect(fn () => SupplierProductRule::factory()
        ->for($supplier)
        ->for($product)
        ->create())
        ->toThrow(QueryException::class);
});

it('seeds the minimal role and permission matrix', function () {
    $this->seed(RolePermissionSeeder::class);

    $admin = Role::query()
        ->where('name', 'admin')
        ->with('permissions')
        ->firstOrFail();
    $viewer = Role::query()
        ->where('name', 'viewer')
        ->with('permissions')
        ->firstOrFail();

    expect(Role::query()->pluck('name')->sort()->values()->all())->toBe([
        'accountant',
        'admin',
        'logistics_manager',
        'supply_manager',
        'viewer',
    ])
        ->and(Permission::query()->count())->toBe(22)
        ->and($admin->permissions)->toHaveCount(22)
        ->and($viewer->permissions->pluck('name')->all())->toBe([
            'view_products',
            'view_calculations',
            'view_supplier_confirmations',
            'view_logistics',
        ]);

    $user = User::factory()->create(['role' => UserRole::Viewer]);
    $user->roles()->attach($admin);

    expect($user->hasRole('admin'))->toBeTrue()
        ->and($user->hasPermissionTo('manage_settings'))->toBeTrue();
});

it('seeds demo company, suppliers, carrier, products, and templates', function () {
    $this->seed(DatabaseSeeder::class);

    $company = Company::query()
        ->where('code', 'DEMO')
        ->with([
            'suppliers.contacts',
            'products.supplierProductRules',
            'carriers.contacts',
            'formTemplates.fields',
        ])
        ->firstOrFail();

    expect($company->name)->toBe('Demo Supply Company')
        ->and(Role::query()->where('name', 'admin')->exists())->toBeTrue()
        ->and($company->suppliers)->toHaveCount(2)
        ->and($company->suppliers->pluck('code')->sort()->values()->all())->toBe([
            'BALTIC-PARTS',
            'NORDIC-DIST',
        ])
        ->and($company->carriers)->toHaveCount(1)
        ->and($company->carriers->first()?->code)->toBe('EXPRESS-ROAD')
        ->and($company->products)->toHaveCount(3)
        ->and($company->products->pluck('sku')->sort()->values()->all())->toBe([
            'AX-150',
            'BRK-200',
            'FLT-010',
        ])
        ->and($company->products->flatMap->supplierProductRules)->toHaveCount(3)
        ->and(FormTemplate::query()->whereBelongsTo($company)->pluck('code')->sort()->values()->all())->toBe([
            'carrier-quote-request',
            'supplier-order-form',
        ])
        ->and(FormTemplate::query()->where('code', 'supplier-order-form')->firstOrFail()->fields()->count())->toBe(4)
        ->and(FormTemplate::query()->where('code', 'carrier-quote-request')->firstOrFail()->fields()->count())->toBe(4);
});

it('connects supplier orders, ai email extractions, confirmations, transport, and logistics records through relationships', function () {
    $company = Company::factory()->create();
    $supplier = Supplier::factory()->for($company)->create(['type' => 'manufacturer']);
    $product = Product::factory()->for($company)->create(['sku' => 'AX-150']);
    $rule = SupplierProductRule::factory()
        ->for($supplier)
        ->for($product)
        ->create([
            'pack_multiple' => 6,
            'pallet_quantity' => 156,
        ]);

    $calculationRun = CalculationRun::factory()
        ->for($company)
        ->for($supplier)
        ->create(['formula_version' => 't0-t1-t2-t3-v1']);
    $proposal = OrderProposal::factory()
        ->for($company)
        ->for($supplier)
        ->for($calculationRun)
        ->create(['total_lines' => 1]);
    $proposalItem = OrderProposalItem::factory()
        ->for($proposal)
        ->for($product)
        ->create([
            'raw_need' => 150,
            'recommended_quantity' => 156,
        ]);

    $supplierOrder = SupplierOrder::factory()
        ->for($company)
        ->for($supplier)
        ->for($proposal, 'orderProposal')
        ->create();
    $supplierOrderItem = SupplierOrderItem::factory()
        ->for($supplierOrder)
        ->for($product)
        ->create(['ordered_quantity' => 156]);

    $emailAccount = EmailAccount::factory()->for($company)->create();
    $emailMessage = EmailMessage::factory()
        ->for($company)
        ->for($emailAccount)
        ->for($supplier, 'relatedSupplier')
        ->for($supplierOrder, 'relatedSupplierOrder')
        ->create();
    $extraction = AiEmailExtraction::factory()
        ->for($emailMessage)
        ->create([
            'output_json' => [
                'supplier_reference' => 'CONF-998',
                'expected_arrival_date' => '2026-08-21',
            ],
        ]);
    $confirmation = SupplierConfirmation::factory()
        ->for($company)
        ->for($supplierOrder)
        ->for($emailMessage)
        ->for($extraction, 'aiEmailExtraction')
        ->create(['supplier_reference' => 'CONF-998']);
    $confirmationItem = SupplierConfirmationItem::factory()
        ->for($confirmation)
        ->for($product)
        ->create();

    $carrier = Carrier::factory()->for($company)->create();
    $quote = CarrierQuote::factory()
        ->for($company)
        ->for($supplierOrder)
        ->for($carrier)
        ->for($emailMessage)
        ->for($extraction, 'aiEmailExtraction')
        ->create(['calculated_score' => 91.125]);
    $logisticsRecord = LogisticsRecord::factory()
        ->for($company)
        ->for($supplierOrder)
        ->for($supplier)
        ->for($carrier)
        ->create(['status' => 'planned']);

    expect($rule->supplier->is($supplier))->toBeTrue()
        ->and($rule->product->is($product))->toBeTrue()
        ->and($proposalItem->orderProposal->is($proposal))->toBeTrue()
        ->and($proposalItem->product->is($product))->toBeTrue()
        ->and($supplierOrderItem->supplierOrder->is($supplierOrder))->toBeTrue()
        ->and($supplierOrder->confirmations()->count())->toBe(1)
        ->and($emailMessage->aiEmailExtractions()->count())->toBe(1)
        ->and($confirmation->aiEmailExtraction->is($extraction))->toBeTrue()
        ->and($confirmationItem->supplierConfirmation->is($confirmation))->toBeTrue()
        ->and($quote->carrier->is($carrier))->toBeTrue()
        ->and($quote->aiEmailExtraction->is($extraction))->toBeTrue()
        ->and($logisticsRecord->supplierOrder->is($supplierOrder))->toBeTrue()
        ->and($company->logisticsRecords()->count())->toBe(1);
});
