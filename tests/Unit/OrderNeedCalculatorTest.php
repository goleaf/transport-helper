<?php

use App\Services\Supply\Calculation\OrderNeedCalculator;

function stageTwoOrderNeedInput(array $overrides = []): array
{
    return array_merge([
        'company_id' => 1,
        'supplier_id' => 1,
        'product_id' => 1,
        't0_date' => '2026-07-01',
        't1_date' => '2026-07-15',
        't2_date' => '2026-08-14',
        't3_date' => '2026-09-01',
        'current_year_sales_for_trend' => 120,
        'last_year_sales_for_trend' => 100,
        'last_year_sales_t0_t1' => 40,
        'last_year_sales_t1_t2' => 100,
        'last_year_sales_t2_t3' => 60,
        'free_stock' => 70,
        'inbound_until_t1' => 0,
        'inbound_t1_t3' => 20,
        'reserved_quantity' => 0,
        'moq' => null,
        'pack_multiple' => 12,
        'pallet_quantity' => null,
        'min_transport_quantity' => null,
        'pallet_strategy' => 'show_only',
        'transport_strategy' => 'show_only',
        'reservation_strategy' => 'reserved_not_removed_from_free_stock',
        'safety_days_rule' => 'manual',
        'strategic_minimum_order_enabled' => false,
    ], $overrides);
}

it('returns raw 150 and recommended 156 for the document example', function () {
    $result = app(OrderNeedCalculator::class)->calculate(stageTwoOrderNeedInput());

    expect($result)->toMatchArray([
        'formula_version' => 'v1',
        'status' => 'ok',
        'trend' => 1.2,
        'need_t0_t1' => 48.0,
        'stock_t1' => 22.0,
        'need_t1_t2' => 120.0,
        'safety_stock' => 72.0,
        'raw_need' => 150.0,
        'recommended_quantity' => 156.0,
        'requires_human_review' => false,
    ]);
});

it('includes all formula steps in the explanation', function () {
    $result = app(OrderNeedCalculator::class)->calculate(stageTwoOrderNeedInput());
    $stepNames = collect($result['explanation']['formula_steps'])->pluck('name')->all();

    expect($result['explanation'])->toHaveKeys([
        'timeline',
        'input_values',
        'formula_steps',
        'rounding_steps',
        'final_result',
    ])
        ->and($stepNames)->toContain('trend')
        ->and($stepNames)->toContain('need_t0_t1')
        ->and($stepNames)->toContain('stock_t1')
        ->and($stepNames)->toContain('need_t1_t2')
        ->and($stepNames)->toContain('safety_stock')
        ->and($stepNames)->toContain('raw_need')
        ->and($stepNames)->toContain('recommended_quantity');
});

it('requires review for an invalid timeline', function () {
    $result = app(OrderNeedCalculator::class)->calculate(stageTwoOrderNeedInput([
        't1_date' => '2026-09-01',
        't2_date' => '2026-08-14',
    ]));

    expect($result['status'])->toBe('needs_review')
        ->and($result['requires_human_review'])->toBeTrue()
        ->and($result['errors'])->toContain('t1_after_t2');
});

it('requires review when reservation strategy is missing', function () {
    $input = stageTwoOrderNeedInput();
    unset($input['reservation_strategy']);

    $result = app(OrderNeedCalculator::class)->calculate($input);

    expect($result['status'])->toBe('needs_review')
        ->and($result['warnings'])->toContain('reservation_strategy_missing');
});

it('does not add reservations twice when they were already removed from stock', function () {
    $result = app(OrderNeedCalculator::class)->calculate(stageTwoOrderNeedInput([
        'pack_multiple' => null,
        'reserved_quantity' => 50,
        'reservation_strategy' => 'reserved_already_removed_from_free_stock',
    ]));

    expect($result['effective_reserved_quantity'])->toBe(0.0)
        ->and($result['raw_need'])->toBe(150.0);
});

it('adds reservations when they were not removed from stock', function () {
    $result = app(OrderNeedCalculator::class)->calculate(stageTwoOrderNeedInput([
        'pack_multiple' => null,
        'reserved_quantity' => 50,
        'reservation_strategy' => 'reserved_not_removed_from_free_stock',
    ]));

    expect($result['effective_reserved_quantity'])->toBe(50.0)
        ->and($result['raw_need'])->toBe(200.0);
});

it('requires review for zero last year sales', function () {
    $result = app(OrderNeedCalculator::class)->calculate(stageTwoOrderNeedInput([
        'last_year_sales_for_trend' => 0,
    ]));

    expect($result['status'])->toBe('needs_review')
        ->and($result['trend'])->toBeNull()
        ->and($result['warnings'])->toContain('insufficient_last_year_sales');
});

it('recommends zero when raw need is negative', function () {
    $result = app(OrderNeedCalculator::class)->calculate(stageTwoOrderNeedInput([
        'pack_multiple' => null,
        'last_year_sales_t0_t1' => 0,
        'last_year_sales_t1_t2' => 10,
        'last_year_sales_t2_t3' => 0,
        'free_stock' => 100,
        'inbound_t1_t3' => 0,
    ]));

    expect($result['raw_need'])->toBe(-88.0)
        ->and($result['recommended_quantity'])->toBe(0.0)
        ->and($result['warnings'])->toContain('raw_need_below_zero');
});

it('documents that safety stock only covers t2 to t3', function () {
    $result = app(OrderNeedCalculator::class)->calculate(stageTwoOrderNeedInput());

    expect($result['explanation']['timeline']['note'])
        ->toContain('Safety stock covers only T2-T3');
});

it('has no forbidden calculation dependencies', function () {
    $source = file_get_contents(dirname(__DIR__, 2).'/app/Services/Supply/Calculation/OrderNeedCalculator.php');

    foreach (['Ai', 'AI', 'Email', 'FormAutofill', 'OpenAI', 'LLM', 'Http', 'Guzzle'] as $forbidden) {
        expect($source)->not->toContain($forbidden);
    }
});
