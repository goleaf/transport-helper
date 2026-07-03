<?php

use App\Models\AiEmailExtraction;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\EmailMessage;
use App\Models\InboundOrder;
use App\Models\InboundOrderItem;
use App\Models\OrderProposalItem;
use App\Models\Product;
use App\Models\SalesHistory;
use App\Models\StockSnapshot;
use App\Models\Supplier;
use App\Models\SupplierOrder;
use App\Models\SupplierProductRule;
use App\Models\User;
use App\Services\Supply\Calculation\OrderNeedCalculator;
use App\Services\Supply\Calculation\OrderProposalGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function stageTwoProposalParameters(array $overrides = []): array
{
    return array_merge([
        'calculation_date' => '2026-07-01',
        't0_date' => '2026-07-01',
        't1_date' => '2026-07-15',
        't2_date' => '2026-08-14',
        't3_date' => '2026-09-01',
        'trend_current_start' => '2026-04-01',
        'trend_current_end' => '2026-07-01',
        'trend_last_start' => '2025-04-01',
        'trend_last_end' => '2025-07-01',
        'last_year_t0_t1_start' => '2025-07-01',
        'last_year_t0_t1_end' => '2025-07-15',
        'last_year_t1_t2_start' => '2025-07-15',
        'last_year_t1_t2_end' => '2025-08-14',
        'last_year_t2_t3_start' => '2025-08-14',
        'last_year_t2_t3_end' => '2025-09-01',
        'reservation_strategy' => 'reserved_not_removed_from_free_stock',
        'rounding_strategy' => [
            'pallet' => 'show_only',
            'transport' => 'show_only',
        ],
        'strategic_minimum_order_enabled' => false,
    ], $overrides);
}

function stageTwoProposalFixture(): array
{
    $company = Company::factory()->create();
    $supplier = Supplier::factory()->for($company)->create();
    $product = Product::factory()->for($company)->create(['sku' => 'SKU-CALC-1']);
    $user = User::factory()->create();

    SupplierProductRule::factory()
        ->for($supplier)
        ->for($product)
        ->create([
            'moq' => null,
            'pack_multiple' => 12,
            'pallet_quantity' => null,
            'min_transport_quantity' => null,
            'order_enabled' => true,
        ]);

    StockSnapshot::factory()->for($company)->for($product)->create([
        'snapshot_date' => '2026-07-01',
        'free_stock' => 70,
    ]);

    foreach ([
        ['2026-06-01', 120],
        ['2025-06-01', 100],
        ['2025-07-10', 40],
        ['2025-07-20', 100],
        ['2025-08-20', 60],
    ] as [$date, $quantity]) {
        SalesHistory::factory()->for($company)->for($product)->create([
            'sales_date' => $date,
            'quantity' => $quantity,
        ]);
    }

    $inboundOrder = InboundOrder::factory()->for($company)->for($supplier)->create([
        'status' => 'open',
        'expected_arrival_date' => '2026-08-01',
    ]);

    InboundOrderItem::factory()->for($inboundOrder)->for($product)->create([
        'ordered_quantity' => 20,
        'expected_arrival_date' => '2026-08-01',
        'status' => 'open',
    ]);

    return compact('company', 'supplier', 'product', 'user');
}

it('creates calculation run proposal items and audit logs for a supplier', function () {
    $fixture = stageTwoProposalFixture();

    $result = app(OrderProposalGenerationService::class)->generateForSupplier(
        $fixture['company'],
        $fixture['supplier'],
        stageTwoProposalParameters(),
        $fixture['user'],
    );

    $proposal = $result['order_proposal'];
    $run = $result['calculation_run'];
    $item = $proposal->items->first();

    expect($run->status)->toBe('completed')
        ->and($run->formula_version)->toBe(OrderNeedCalculator::FORMULA_VERSION)
        ->and($proposal->status->value)->toBe('draft')
        ->and($proposal->calculationRun->is($run))->toBeTrue()
        ->and($item)->toBeInstanceOf(OrderProposalItem::class)
        ->and((float) $item->raw_need)->toBe(150.0)
        ->and((float) $item->recommended_quantity)->toBe(156.0)
        ->and($item->explanation_json)->toHaveKey('formula_steps')
        ->and($item->requires_human_review)->toBeFalse();

    expect(AuditLog::query()->where('event_type', 'calculation_run_completed')->exists())->toBeTrue()
        ->and(AuditLog::query()->where('event_type', 'order_proposal_created')->exists())->toBeTrue()
        ->and(AuditLog::query()->where('event_type', 'order_proposal_item_calculated')->exists())->toBeTrue();
});

it('marks generated items as needs review when data is missing', function () {
    $company = Company::factory()->create();
    $supplier = Supplier::factory()->for($company)->create();
    $product = Product::factory()->for($company)->create();

    SupplierProductRule::factory()->for($supplier)->for($product)->create([
        'order_enabled' => true,
    ]);

    StockSnapshot::factory()->for($company)->for($product)->create([
        'snapshot_date' => '2026-07-01',
        'free_stock' => 70,
    ]);

    $result = app(OrderProposalGenerationService::class)->generateForSupplier(
        $company,
        $supplier,
        stageTwoProposalParameters(),
    );

    $item = $result['order_proposal']->items->first();

    expect($item->status->value)->toBe('needs_review')
        ->and($item->requires_human_review)->toBeTrue()
        ->and($item->warnings_json)->toContain('insufficient_last_year_sales');
});

it('filters by requested product ids', function () {
    $fixture = stageTwoProposalFixture();
    $secondProduct = Product::factory()->for($fixture['company'])->create();

    SupplierProductRule::factory()
        ->for($fixture['supplier'])
        ->for($secondProduct)
        ->create(['order_enabled' => true]);

    $result = app(OrderProposalGenerationService::class)->generateForSupplier(
        $fixture['company'],
        $fixture['supplier'],
        stageTwoProposalParameters([
            'product_ids' => [$fixture['product']->id],
        ]),
        $fixture['user'],
    );

    expect($result['order_proposal']->items)->toHaveCount(1)
        ->and($result['order_proposal']->items->first()->product_id)->toBe($fixture['product']->id);
});

it('does not create supplier orders during proposal generation', function () {
    $fixture = stageTwoProposalFixture();

    app(OrderProposalGenerationService::class)->generateForSupplier(
        $fixture['company'],
        $fixture['supplier'],
        stageTwoProposalParameters(),
        $fixture['user'],
    );

    expect(SupplierOrder::query()->count())->toBe(0);
});

it('does not create email messages or extraction records', function () {
    $fixture = stageTwoProposalFixture();

    app(OrderProposalGenerationService::class)->generateForSupplier(
        $fixture['company'],
        $fixture['supplier'],
        stageTwoProposalParameters(),
        $fixture['user'],
    );

    expect(EmailMessage::query()->count())->toBe(0)
        ->and(AiEmailExtraction::query()->count())->toBe(0);
});
