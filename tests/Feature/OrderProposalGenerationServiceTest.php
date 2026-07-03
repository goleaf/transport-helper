<?php

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\InboundOrder;
use App\Models\InboundOrderItem;
use App\Models\OrderProposalItem;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\SalesHistory;
use App\Models\StockSnapshot;
use App\Models\Supplier;
use App\Models\SupplierProductRule;
use App\Models\User;
use App\Services\Supply\OrderNeedCalculator;
use App\Services\Supply\OrderProposalGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('generates an order proposal from supplier rules, stock, sales, inbound orders, and reservations', function () {
    $company = Company::factory()->create();
    $supplier = Supplier::factory()->for($company)->create(['type' => 'manufacturer']);
    $product = Product::factory()->for($company)->create(['sku' => 'AX-150']);
    $user = User::factory()->create();

    SupplierProductRule::factory()
        ->for($supplier)
        ->for($product)
        ->create([
            'moq' => 1,
            'pack_multiple' => 12,
            'pallet_quantity' => 156,
            'min_transport_quantity' => null,
            'order_enabled' => true,
        ]);

    SalesHistory::factory()->create([
        'company_id' => $company->getKey(),
        'product_id' => $product->getKey(),
        'sales_date' => '2026-06-01',
        'quantity' => 100,
    ]);
    SalesHistory::factory()->create([
        'company_id' => $company->getKey(),
        'product_id' => $product->getKey(),
        'sales_date' => '2025-06-01',
        'quantity' => 100,
    ]);
    SalesHistory::factory()->create([
        'company_id' => $company->getKey(),
        'product_id' => $product->getKey(),
        'sales_date' => '2025-07-10',
        'quantity' => 10,
    ]);
    SalesHistory::factory()->create([
        'company_id' => $company->getKey(),
        'product_id' => $product->getKey(),
        'sales_date' => '2025-07-20',
        'quantity' => 120,
    ]);
    SalesHistory::factory()->create([
        'company_id' => $company->getKey(),
        'product_id' => $product->getKey(),
        'sales_date' => '2025-08-05',
        'quantity' => 30,
    ]);

    StockSnapshot::factory()->create([
        'company_id' => $company->getKey(),
        'product_id' => $product->getKey(),
        'snapshot_date' => '2026-07-01',
        'free_stock' => 20,
    ]);

    $inboundUntilT1 = InboundOrder::factory()
        ->for($company)
        ->for($supplier)
        ->create([
            'status' => 'open',
            'expected_arrival_date' => '2026-07-10',
        ]);
    InboundOrderItem::factory()
        ->for($inboundUntilT1)
        ->for($product)
        ->create([
            'ordered_quantity' => 5,
            'received_quantity' => 0,
            'expected_arrival_date' => '2026-07-10',
        ]);

    $inboundT1T3 = InboundOrder::factory()
        ->for($company)
        ->for($supplier)
        ->create([
            'status' => 'open',
            'expected_arrival_date' => '2026-07-20',
        ]);
    InboundOrderItem::factory()
        ->for($inboundT1T3)
        ->for($product)
        ->create([
            'ordered_quantity' => 15,
            'received_quantity' => 0,
            'expected_arrival_date' => '2026-07-20',
        ]);

    Reservation::factory()->create([
        'company_id' => $company->getKey(),
        'product_id' => $product->getKey(),
        'quantity' => 30,
        'expected_usage_date' => '2026-07-25',
        'status' => 'active',
    ]);

    $result = app(OrderProposalGenerationService::class)->generate([
        'company_id' => $company->getKey(),
        'supplier_id' => $supplier->getKey(),
        'created_by_user_id' => $user->getKey(),
        't0_date' => '2026-07-01',
        't1_date' => '2026-07-15',
        't2_date' => '2026-08-01',
        't3_date' => '2026-08-15',
        'current_year_trend_start_date' => '2026-04-01',
        'current_year_trend_end_date' => '2026-07-01',
        'last_year_trend_start_date' => '2025-04-01',
        'last_year_trend_end_date' => '2025-07-01',
        'rounding_strategy' => [
            'pallet_quantity' => 'show_only',
            'min_transport_quantity' => 'show_only',
        ],
    ]);

    $calculationRun = $result['calculation_run'];
    $proposal = $result['order_proposal'];
    $item = $proposal->items->first();

    $this->assertModelExists($calculationRun);
    $this->assertModelExists($proposal);
    expect($item)->toBeInstanceOf(OrderProposalItem::class)
        ->and($proposal->status->value)->toBe('draft')
        ->and($proposal->total_lines)->toBe(1)
        ->and($calculationRun->formula_version)->toBe(OrderNeedCalculator::FORMULA_VERSION)
        ->and($calculationRun->status)->toBe('completed')
        ->and((float) $item->trend)->toBe(1.0)
        ->and((float) $item->need_t0_t1)->toBe(10.0)
        ->and((float) $item->stock_t1)->toBe(15.0)
        ->and((float) $item->need_t1_t2)->toBe(120.0)
        ->and((float) $item->safety_stock)->toBe(30.0)
        ->and((float) $item->inbound_until_t1)->toBe(5.0)
        ->and((float) $item->inbound_t1_t3)->toBe(15.0)
        ->and((float) $item->reserved_quantity)->toBe(30.0)
        ->and((float) $item->raw_need)->toBe(150.0)
        ->and((float) $item->recommended_quantity)->toBe(156.0)
        ->and($item->requires_human_review)->toBeFalse()
        ->and($item->warnings_json)->toBe([])
        ->and($item->explanation_json['final_result'])->toMatchArray([
            'status' => 'draft',
            'recommended_quantity' => 156.0,
        ]);

    $auditLog = AuditLog::query()
        ->where('event_type', 'order_proposal.generated')
        ->firstOrFail();

    expect($auditLog->auditable->is($proposal))->toBeTrue()
        ->and($auditLog->company->is($company))->toBeTrue()
        ->and($auditLog->user->is($user))->toBeTrue()
        ->and($auditLog->new_values_json)->toMatchArray([
            'calculation_run_id' => $calculationRun->getKey(),
            'order_proposal_id' => $proposal->getKey(),
            'total_lines' => 1,
            'status' => 'draft',
        ])
        ->and($auditLog->metadata_json)->toMatchArray([
            'formula_version' => OrderNeedCalculator::FORMULA_VERSION,
            'requires_human_review' => false,
        ]);
});
