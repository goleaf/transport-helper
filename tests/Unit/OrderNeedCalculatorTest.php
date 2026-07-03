<?php

use App\Services\Supply\OrderNeedCalculator;
use App\Services\Supply\OrderRoundingService;
use App\Services\Supply\TrendCalculator;

function makeOrderNeedCalculator(): OrderNeedCalculator
{
    return new OrderNeedCalculator(
        new TrendCalculator,
        new OrderRoundingService,
    );
}

function baseOrderNeedInput(array $overrides = []): array
{
    return array_merge([
        'company_id' => 1,
        'supplier_id' => 10,
        'product_id' => 100,
        't0_date' => '2026-07-01',
        't1_date' => '2026-07-15',
        't2_date' => '2026-08-01',
        't3_date' => '2026-08-15',
        'current_year_sales_for_trend' => 100,
        'last_year_sales_for_trend' => 100,
        'last_year_sales_t0_t1' => 0,
        'last_year_sales_t1_t2' => 150,
        'last_year_sales_t2_t3' => 0,
        'free_stock' => 0,
        'inbound_until_t1' => 0,
        'inbound_t1_t3' => 0,
        'reserved_quantity' => 0,
        'moq' => null,
        'pack_multiple' => null,
        'pallet_quantity' => null,
        'min_transport_quantity' => null,
        'rounding_strategy' => [],
        'reservation_strategy' => 'include',
        'safety_days_rule' => null,
        'strategic_minimum_order_enabled' => false,
    ], $overrides);
}

test('test_document_example_returns_150_and_156', function () {
    $result = makeOrderNeedCalculator()->calculate(baseOrderNeedInput([
        'current_year_sales_for_trend' => 120,
        'last_year_sales_for_trend' => 100,
        'last_year_sales_t0_t1' => 40,
        'last_year_sales_t1_t2' => 100,
        'last_year_sales_t2_t3' => 60,
        'free_stock' => 70,
        'inbound_until_t1' => 0,
        'inbound_t1_t3' => 20,
        'reserved_quantity' => 0,
        'pack_multiple' => 12,
    ]));

    expect($result)->toMatchArray([
        'formula_version' => OrderNeedCalculator::FORMULA_VERSION,
        'status' => 'draft',
        'trend' => 1.2,
        'need_t0_t1' => 48.0,
        'stock_t1' => 22.0,
        'need_t1_t2' => 120.0,
        'safety_stock' => 72.0,
        'raw_need' => 150.0,
        'recommended_quantity' => 156.0,
        'warnings' => [],
        'requires_human_review' => false,
    ])
        ->and($result['applied_rules']['pack_multiple'])->toMatchArray([
            'value' => 12.0,
            'from' => 150.0,
            'to' => 156.0,
        ])
        ->and($result['explanation']['intermediate_values'])->toMatchArray([
            'trend' => 1.2,
            'need_t0_t1' => 48.0,
            'stock_t1' => 22.0,
            'need_t1_t2' => 120.0,
            'safety_stock' => 72.0,
            'raw_need' => 150.0,
        ])
        ->and($result['explanation']['final_result'])->toMatchArray([
            'status' => 'draft',
            'recommended_quantity' => 156.0,
        ]);
});

test('raw_need_negative_returns_zero', function () {
    $result = makeOrderNeedCalculator()->calculate(baseOrderNeedInput([
        'last_year_sales_t1_t2' => 10,
        'free_stock' => 100,
    ]));

    expect($result)->toMatchArray([
        'status' => 'draft',
        'raw_need' => -90.0,
        'recommended_quantity' => 0.0,
        'requires_human_review' => false,
    ]);
});

test('moq_applied_when_raw_need_positive', function () {
    $result = makeOrderNeedCalculator()->calculate(baseOrderNeedInput([
        'last_year_sales_t1_t2' => 10,
        'moq' => 24,
    ]));

    expect($result)->toMatchArray([
        'raw_need' => 10.0,
        'recommended_quantity' => 24.0,
    ])
        ->and($result['applied_rules']['moq'])->toMatchArray([
            'value' => 24.0,
            'from' => 10.0,
            'to' => 24.0,
        ]);
});

test('pack_multiple_rounds_up', function () {
    $result = makeOrderNeedCalculator()->calculate(baseOrderNeedInput([
        'last_year_sales_t1_t2' => 25,
        'pack_multiple' => 12,
    ]));

    expect($result)->toMatchArray([
        'raw_need' => 25.0,
        'recommended_quantity' => 36.0,
    ])
        ->and($result['applied_rules']['pack_multiple'])->toMatchArray([
            'value' => 12.0,
            'from' => 25.0,
            'to' => 36.0,
        ]);
});

test('pallet_show_only_does_not_change_quantity', function () {
    $result = makeOrderNeedCalculator()->calculate(baseOrderNeedInput([
        'last_year_sales_t1_t2' => 40,
        'pallet_quantity' => 50,
    ]));

    expect($result)->toMatchArray([
        'status' => 'needs_review',
        'raw_need' => 40.0,
        'recommended_quantity' => 40.0,
        'requires_human_review' => true,
    ])
        ->and($result['warnings'])->toContain('pallet_quantity_not_full_pallet')
        ->and($result['applied_rules']['pallet_quantity'])->toMatchArray([
            'strategy' => 'show_only',
            'value' => 50.0,
            'would_round_to' => 50.0,
        ]);
});

test('enforce_full_pallet_changes_quantity', function () {
    $result = makeOrderNeedCalculator()->calculate(baseOrderNeedInput([
        'last_year_sales_t1_t2' => 40,
        'pallet_quantity' => 50,
        'rounding_strategy' => [
            'pallet_quantity' => 'enforce_full_pallet',
        ],
    ]));

    expect($result)->toMatchArray([
        'status' => 'draft',
        'raw_need' => 40.0,
        'recommended_quantity' => 50.0,
        'warnings' => [],
        'requires_human_review' => false,
    ])
        ->and($result['applied_rules']['pallet_quantity'])->toMatchArray([
            'strategy' => 'enforce_full_pallet',
            'value' => 50.0,
            'from' => 40.0,
            'to' => 50.0,
        ]);
});

test('missing_last_year_sales_requires_review', function () {
    $result = makeOrderNeedCalculator()->calculate(baseOrderNeedInput([
        'last_year_sales_for_trend' => 0,
    ]));

    expect($result)->toMatchArray([
        'status' => 'needs_review',
        'trend' => null,
        'raw_need' => null,
        'recommended_quantity' => 0.0,
        'requires_human_review' => true,
    ])
        ->and($result['warnings'])->toContain('insufficient_last_year_sales')
        ->and($result['applied_rules']['trend'])->toMatchArray([
            'status' => 'needs_review',
            'applied_fallback' => null,
        ]);
});

test('explanation_contains_all_formula_components', function () {
    $result = makeOrderNeedCalculator()->calculate(baseOrderNeedInput([
        'current_year_sales_for_trend' => 120,
        'last_year_sales_for_trend' => 100,
        'last_year_sales_t0_t1' => 40,
        'last_year_sales_t1_t2' => 100,
        'last_year_sales_t2_t3' => 60,
        'free_stock' => 70,
        'inbound_t1_t3' => 20,
        'pack_multiple' => 12,
    ]));

    expect($result['explanation'])->toHaveKeys([
        'dates',
        'formula_steps',
        'input_values',
        'intermediate_values',
        'rounding_steps',
        'warnings',
        'final_result',
    ])
        ->and($result['explanation']['formula_steps'])->toContain('trend = current_year_sales_for_trend / last_year_sales_for_trend')
        ->and($result['explanation']['formula_steps'])->toContain('need_t0_t1 = last_year_sales_t0_t1 * trend')
        ->and($result['explanation']['formula_steps'])->toContain('stock_t1 = free_stock + inbound_until_t1 - need_t0_t1')
        ->and($result['explanation']['formula_steps'])->toContain('need_t1_t2 = last_year_sales_t1_t2 * trend')
        ->and($result['explanation']['formula_steps'])->toContain('safety_stock = last_year_sales_t2_t3 * trend')
        ->and($result['explanation']['formula_steps'])->toContain('raw_need = need_t1_t2 + safety_stock - stock_t1 - inbound_t1_t3 + reserved_quantity')
        ->and($result['explanation']['input_values'])->toHaveKeys([
            'current_year_sales_for_trend',
            'last_year_sales_for_trend',
            'last_year_sales_t0_t1',
            'last_year_sales_t1_t2',
            'last_year_sales_t2_t3',
            'free_stock',
            'inbound_until_t1',
            'inbound_t1_t3',
            'reserved_quantity',
        ])
        ->and($result['explanation']['intermediate_values'])->toHaveKeys([
            'trend',
            'need_t0_t1',
            'stock_t1',
            'need_t1_t2',
            'safety_stock',
            'reserved_quantity',
            'raw_need',
        ]);
});

test('calculator_has_no_dependency_on_ai_or_email_classes', function () {
    $reflection = new ReflectionClass(OrderNeedCalculator::class);
    $constructorDependencies = collect($reflection->getConstructor()?->getParameters() ?? [])
        ->map(fn (ReflectionParameter $parameter) => $parameter->getType()?->getName())
        ->values()
        ->all();

    expect($constructorDependencies)->toBe([
        TrendCalculator::class,
        OrderRoundingService::class,
    ]);

    foreach ($constructorDependencies as $dependency) {
        expect($dependency)
            ->not->toContain('Email')
            ->not->toContain('Ai')
            ->not->toContain('AI');
    }

    $source = file_get_contents((string) $reflection->getFileName());

    expect($source)
        ->not->toContain('Email')
        ->not->toContain('AiEmail')
        ->not->toContain('AI');
});
